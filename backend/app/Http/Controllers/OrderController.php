<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentType;
use App\Exceptions\BusinessException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\StateTransitionException;
use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Repositories\OrderRepository;
use App\Services\PermissionService;
use App\Services\StateMachine\OrderStateMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected PermissionService $permissionService,
    ) {
        $this->middleware('permission:order.view')->only(['index', 'show']);
        $this->middleware('permission:order.create')->only(['store']);
        $this->middleware('permission:order.edit')->only(['update']);
        $this->middleware('permission:order.delete')->only(['destroy']);
        $this->middleware('permission:order.approve')->only(['approve']);
    }

    public function index(Request $request)
    {
        $with = ['supplier:id,name', 'distributor:id,name,type', 'creator:id,name'];
        $paginator = $this->orderRepository->listForUser($request->user(), $request, $with);

        return OrderResource::collection($paginator);
    }

    public function store(OrderRequest $request)
    {
        $user = $request->user();
        $this->permissionService->forUser($user)->ensureCanCreateOrder();

        $data = $request->validated();

        $order = DB::transaction(function () use ($data, $user) {
            $subtotal = '0';
            $supplierId = $data['supplier_id'];
            $itemsPayload = [];

            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);

                if (!$product) {
                    throw BusinessException::withCode(
                        "商品不存在: {$item['product_id']}",
                        'PRODUCT_NOT_FOUND'
                    );
                }

                if ($product->supplier_id !== $supplierId) {
                    throw BusinessException::withCode(
                        "商品 {$product->name} 不属于该供应商",
                        'PRODUCT_SUPPLIER_MISMATCH'
                    );
                }

                $lineSubtotal = bcmul((string) $item['quantity'], (string) $item['unit_price'], 2);
                $subtotal = bcadd($subtotal, $lineSubtotal, 2);

                $itemsPayload[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'product_sku' => $product->sku ?? '',
                    'specification' => $product->specification,
                    'unit' => $product->unit ?? 'pcs',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $lineSubtotal,
                    'discount' => 0,
                    'tax' => 0,
                    'total' => $lineSubtotal,
                ];
            }

            $tax = $data['tax'] ?? 0;
            $discount = $data['discount'] ?? 0;
            $shipping = $data['shipping'] ?? 0;
            $total = bcsub(bcadd(bcadd($subtotal, (string) $tax, 2), (string) $shipping, 2), (string) $discount, 2);

            if ((float) $total < 0) {
                throw BusinessException::withCode('订单金额不能为负数', 'INVALID_ORDER_AMOUNT');
            }

            $distributorId = $data['distributor_id'];

            $order = Order::create([
                'order_no' => 'ORD'.date('YmdHis').str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
                'type' => $data['type'],
                'supplier_id' => $supplierId,
                'distributor_id' => $distributorId,
                'created_by' => $user->id,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'shipping' => $shipping,
                'total' => $total,
                'paid_amount' => 0,
                'payment_status' => 'unpaid',
                'status' => OrderStatus::PENDING->value,
                'shipping_address' => $data['shipping_address'] ?? null,
                'billing_address' => $data['billing_address'] ?? null,
                'remark' => $data['remark'] ?? null,
            ]);

            foreach ($itemsPayload as $payload) {
                $payload['order_id'] = $order->id;
                OrderItem::create($payload);
            }

            return $order;
        });

        return new OrderResource($order->load(['items', 'supplier', 'distributor', 'creator']));
    }

    public function show(Request $request, Order $order)
    {
        $this->orderRepository->findForUserOrFail(
            $request->user(),
            $order->id,
            ['items.product', 'supplier', 'distributor', 'creator', 'payments']
        );

        return new OrderResource($order->load(['items.product', 'supplier', 'distributor', 'creator', 'payments']));
    }

    public function update(OrderRequest $request, Order $order)
    {
        $this->orderRepository->findForUserOrFail($request->user(), $order->id);

        if ($order->getStatusEnum()->isTerminal()) {
            throw StateTransitionException::terminalState($order->getStatusEnum()->label());
        }

        $order->update($request->safe()->except([
            'items', 'type', 'supplier_id', 'distributor_id',
            'created_by', 'order_no', 'subtotal', 'total',
            'paid_amount', 'payment_status', 'status',
        ]));

        return new OrderResource($order->load(['items', 'supplier', 'distributor', 'creator']));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $user = $request->user();
        $this->orderRepository->findForUserOrFail($user, $order->id);

        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $targetStatus = OrderStatus::tryFrom($validated['status']);

        if (!$targetStatus) {
            throw BusinessException::withCode(
                '无效的订单状态值',
                'INVALID_ORDER_STATUS',
                [
                    'allowed' => array_column(OrderStatus::cases(), 'value'),
                ]
            );
        }

        $this->permissionService->forUser($user)->ensureCanUpdateOrderStatus($order, $targetStatus);

        try {
            $stateMachine = new OrderStateMachine($order);
            $updatedOrder = $stateMachine->transitionTo($targetStatus);
        } catch (\DomainException $e) {
            throw new StateTransitionException($e->getMessage());
        }

        return new OrderResource($updatedOrder->load(['items', 'supplier', 'distributor', 'creator']));
    }

    public function destroy(Request $request, Order $order)
    {
        $this->orderRepository->findForUserOrFail($request->user(), $order->id);

        if ($order->payments()->where('type', PaymentType::ESCROW_DEPOSIT->value)->exists()) {
            throw new BusinessException('订单已产生付款记录，请先处理退款后再删除');
        }

        $order->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function approve(Request $request, Order $order)
    {
        $user = $request->user();
        $this->orderRepository->findForUserOrFail($user, $order->id);

        $validated = $request->validate([
            'status' => ['required', 'in:confirmed,cancelled,rejected'],
            'remark' => ['nullable', 'string'],
        ]);

        $targetStatus = OrderStatus::tryFrom($validated['status']);

        $this->permissionService->forUser($user)->ensureCanApproveOrder($order);

        try {
            $stateMachine = new OrderStateMachine($order);
            $updatedOrder = $stateMachine->transitionTo($targetStatus, [
                'remark' => $validated['remark'] ?? null,
            ]);
        } catch (\DomainException $e) {
            throw new StateTransitionException($e->getMessage());
        }

        return new OrderResource($updatedOrder->load(['items', 'supplier', 'distributor', 'creator']));
    }
}
