<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::visibleTo($request->user())
            ->with(['supplier:id,name', 'distributor:id,name,type', 'creator:id,name']);

        $this->applySearch($query, $request, ['order_no', 'tracking_no']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status'));
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        if ($request->filled('distributor_id')) {
            $query->where('distributor_id', $request->integer('distributor_id'));
        }

        return OrderResource::collection(
            $query->latest()->paginate($this->perPage($request))
        );
    }

    public function store(OrderRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $order = DB::transaction(function () use ($data, $user) {
            $subtotal = '0';
            $supplierId = $data['supplier_id'] ?? null;
            $itemsPayload = [];

            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);
                $lineSubtotal = bcmul((string) $item['quantity'], (string) $item['unit_price'], 2);
                $subtotal = bcadd($subtotal, $lineSubtotal, 2);
                $supplierId = $supplierId ?? ($product?->supplier_id);

                $itemsPayload[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $product?->name ?? '',
                    'product_sku' => $product?->sku ?? '',
                    'specification' => $product?->specification,
                    'unit' => $product?->unit ?? 'pcs',
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

            $distributorId = $data['distributor_id'] ?? ($user->isDistributor() ? $user->distributor_id : null);

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
                'status' => 'pending',
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
        Order::visibleTo($request->user())->where('id', $order->id)->firstOrFail();

        return new OrderResource($order->load(['items.product', 'supplier', 'distributor', 'creator', 'payments']));
    }

    public function update(OrderRequest $request, Order $order)
    {
        Order::visibleTo($request->user())->where('id', $order->id)->firstOrFail();

        $order->update($request->safe()->except(['items', 'type', 'supplier_id', 'distributor_id', 'created_by', 'order_no', 'subtotal', 'total', 'paid_amount', 'payment_status']));

        return new OrderResource($order->load(['items', 'supplier', 'distributor', 'creator']));
    }

    public function updateStatus(Request $request, Order $order)
    {
        Order::visibleTo($request->user())->where('id', $order->id)->firstOrFail();

        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,processing,shipped,delivered,completed,cancelled,refunded'],
        ]);

        $status = $validated['status'];
        $order->status = $status;

        $timestamps = [
            'confirmed' => 'confirmed_at',
            'shipped' => 'shipped_at',
            'delivered' => 'delivered_at',
            'completed' => 'completed_at',
        ];

        if (isset($timestamps[$status])) {
            $order->{$timestamps[$status]} = now();
        }

        $order->save();

        return new OrderResource($order->load(['items', 'supplier', 'distributor', 'creator']));
    }

    public function destroy(Request $request, Order $order)
    {
        Order::visibleTo($request->user())->where('id', $order->id)->firstOrFail();

        $order->delete();

        return response()->json(['message' => '删除成功']);
    }
}
