<?php

namespace App\Http\Controllers;

use App\Enums\ShipmentStatus;
use App\Exceptions\BusinessException;
use App\Exceptions\StateTransitionException;
use App\Models\Shipment;
use App\Repositories\ShipmentRepository;
use App\Services\PermissionService;
use App\Services\StateMachine\ShipmentStateMachine;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function __construct(
        protected ShipmentRepository $shipmentRepository,
        protected PermissionService $permissionService,
    ) {
    }

    public function index(Request $request)
    {
        $with = ['order', 'shippingMethod', 'originMarket', 'destinationMarket'];
        $paginator = $this->shipmentRepository->listForUser($request->user(), $request, $with);

        return response()->json($paginator);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'tracking_no' => 'required|string|max:100|unique:shipments,tracking_no',
            'order_id' => 'nullable|exists:orders,id',
            'shipping_method_id' => 'nullable|exists:shipping_methods,id',
            'carrier' => 'nullable|string|max:255',
            'origin_warehouse_id' => 'nullable|exists:warehouses,id',
            'destination_warehouse_id' => 'nullable|exists:warehouses,id',
            'origin_market_id' => 'nullable|exists:markets,id',
            'destination_market_id' => 'nullable|exists:markets,id',
            'sender_name' => 'nullable|string|max:255',
            'sender_address' => 'nullable|string',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:50',
            'receiver_address' => 'required|string',
            'receiver_email' => 'nullable|email',
            'receiver_city' => 'nullable|string|max:255',
            'receiver_state' => 'nullable|string|max:255',
            'receiver_postal_code' => 'nullable|string|max:50',
            'receiver_country' => 'nullable|string|max:10',
            'weight' => 'nullable|decimal:0,3',
            'volume' => 'nullable|decimal:0,3',
            'packages' => 'nullable|integer',
            'declared_value' => 'nullable|decimal:0,2',
            'currency' => 'nullable|string|max:10',
            'shipping_cost' => 'nullable|decimal:0,2',
            'insurance_cost' => 'nullable|decimal:0,2',
            'fuel_surcharge' => 'nullable|decimal:0,2',
            'other_fee' => 'nullable|decimal:0,2',
            'total_cost' => 'nullable|decimal:0,2',
            'status' => 'sometimes|in:pending,picked_up,shipped,in_transit,customs,out_for_delivery,delivered,failed,returned,cancelled',
            'remark' => 'nullable|string',
        ]);

        if (!empty($validated['order_id'])) {
            $orderRepo = new \App\Repositories\OrderRepository();
            $orderRepo->findForUserOrFail($user, (int) $validated['order_id']);
        }

        $shipment = Shipment::create($validated);

        return response()->json($shipment->load(['order', 'shippingMethod', 'originMarket', 'destinationMarket']));
    }

    public function show(Request $request, Shipment $shipment)
    {
        $this->shipmentRepository->findForUserOrFail(
            $request->user(),
            $shipment->id,
            ['order', 'shippingMethod', 'originWarehouse', 'destinationWarehouse', 'originMarket', 'destinationMarket', 'declarations']
        );

        return response()->json(
            $shipment->load([
                'order', 'shippingMethod',
                'originWarehouse', 'destinationWarehouse',
                'originMarket', 'destinationMarket',
                'declarations',
            ])
        );
    }

    public function update(Request $request, Shipment $shipment)
    {
        $user = $request->user();
        $this->shipmentRepository->findForUserOrFail($user, $shipment->id);

        $this->permissionService->forUser($user)->ensureCanManageShipment($shipment);

        if ($shipment->getStatusEnum()->isTerminal()) {
            throw new StateTransitionException("物流已处于终态（{$shipment->getStatusEnum()->label()}），无法修改");
        }

        $validated = $request->validate([
            'tracking_no' => 'sometimes|string|max:100|unique:shipments,tracking_no,' . $shipment->id,
            'order_id' => 'nullable|exists:orders,id',
            'shipping_method_id' => 'nullable|exists:shipping_methods,id',
            'carrier' => 'nullable|string|max:255',
            'origin_warehouse_id' => 'nullable|exists:warehouses,id',
            'destination_warehouse_id' => 'nullable|exists:warehouses,id',
            'origin_market_id' => 'nullable|exists:markets,id',
            'destination_market_id' => 'nullable|exists:markets,id',
            'sender_name' => 'nullable|string|max:255',
            'sender_address' => 'nullable|string',
            'receiver_name' => 'sometimes|string|max:255',
            'receiver_phone' => 'sometimes|string|max:50',
            'receiver_address' => 'sometimes|string',
            'receiver_email' => 'nullable|email',
            'receiver_city' => 'nullable|string|max:255',
            'receiver_state' => 'nullable|string|max:255',
            'receiver_postal_code' => 'nullable|string|max:50',
            'receiver_country' => 'nullable|string|max:10',
            'weight' => 'nullable|decimal:0,3',
            'volume' => 'nullable|decimal:0,3',
            'packages' => 'nullable|integer',
            'declared_value' => 'nullable|decimal:0,2',
            'currency' => 'nullable|string|max:10',
            'shipping_cost' => 'nullable|decimal:0,2',
            'insurance_cost' => 'nullable|decimal:0,2',
            'fuel_surcharge' => 'nullable|decimal:0,2',
            'other_fee' => 'nullable|decimal:0,2',
            'total_cost' => 'nullable|decimal:0,2',
            'status' => 'sometimes|in:pending,picked_up,shipped,in_transit,customs,out_for_delivery,delivered,failed,returned,cancelled',
            'remark' => 'nullable|string',
        ]);

        $shipment->update($validated);

        return response()->json($shipment->load(['order', 'shippingMethod', 'originMarket', 'destinationMarket']));
    }

    public function destroy(Request $request, Shipment $shipment)
    {
        $this->shipmentRepository->findForUserOrFail($request->user(), $shipment->id);

        if (!$shipment->isPending() && !$request->user()->isPlatform()) {
            throw new BusinessException('非待发货状态的物流记录仅允许平台管理员删除');
        }

        $shipment->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function updateStatus(Request $request, Shipment $shipment)
    {
        $user = $request->user();
        $this->shipmentRepository->findForUserOrFail($user, $shipment->id);

        $validated = $request->validate([
            'status' => 'required|string',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $targetStatus = ShipmentStatus::tryFrom($validated['status']);

        if (!$targetStatus) {
            throw BusinessException::withCode(
                '无效的物流状态值',
                'INVALID_SHIPMENT_STATUS',
                [
                    'allowed' => array_column(ShipmentStatus::cases(), 'value'),
                ]
            );
        }

        $this->permissionService->forUser($user)->ensureCanManageShipment($shipment);

        try {
            $stateMachine = new ShipmentStateMachine($shipment);
            $updatedShipment = $stateMachine->transitionTo($targetStatus, [
                'location' => $validated['location'] ?? '',
                'description' => $validated['description'] ?? '',
            ]);
        } catch (\DomainException $e) {
            throw new StateTransitionException($e->getMessage());
        }

        return response()->json(
            $updatedShipment->load(['order', 'shippingMethod', 'originMarket', 'destinationMarket'])
        );
    }
}
