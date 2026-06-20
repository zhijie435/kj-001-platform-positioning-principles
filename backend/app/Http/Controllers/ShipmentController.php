<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\CrossBorderStatusService;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function __construct(
        private CrossBorderStatusService $statusService,
    ) {}

    public function index(Request $request)
    {
        $query = Shipment::visibleTo($request->user())
            ->with(['order', 'shippingMethod', 'originMarket', 'destinationMarket']);

        $this->applySearch($query, $request, ['tracking_no', 'receiver_name', 'receiver_phone']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->integer('order_id'));
        }

        if ($request->filled('destination_market_id')) {
            $query->where('destination_market_id', $request->integer('destination_market_id'));
        }

        if ($request->filled('shipping_method_id')) {
            $query->where('shipping_method_id', $request->integer('shipping_method_id'));
        }

        return response()->json(
            $query->latest()->paginate($this->perPage($request))
        );
    }

    public function store(Request $request)
    {
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

        $shipment = Shipment::create($validated);

        return response()->json($shipment->load(['order', 'shippingMethod', 'originMarket', 'destinationMarket']));
    }

    public function show(Request $request, Shipment $shipment)
    {
        Shipment::visibleTo($request->user())->where('id', $shipment->id)->firstOrFail();

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
        Shipment::visibleTo($request->user())->where('id', $shipment->id)->firstOrFail();

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
        Shipment::visibleTo($request->user())->where('id', $shipment->id)->firstOrFail();

        $shipment->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function updateStatus(Request $request, Shipment $shipment)
    {
        Shipment::visibleTo($request->user())->where('id', $shipment->id)->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:pending,picked_up,shipped,in_transit,customs,out_for_delivery,delivered,failed,returned,cancelled',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $targetStatus = $validated['status'];

        $validation = $this->statusService->validateShipmentTransition($shipment, $targetStatus);

        if (!$validation['valid']) {
            return response()->json([
                'message' => $validation['message'],
            ], 422);
        }

        $statusField = match ($targetStatus) {
            'shipped' => 'shipped_at',
            'in_transit' => 'in_transit_at',
            'customs' => 'customs_at',
            'delivered' => 'delivered_at',
            'failed' => 'failed_at',
            default => null,
        };

        $update = ['status' => $targetStatus];
        if ($statusField && !$shipment->$statusField) {
            $update[$statusField] = now();
        }

        $shipment->addTrackingEvent(
            $targetStatus,
            $validated['location'] ?? '',
            $validated['description'] ?? ''
        );
        $shipment->update($update);

        return response()->json($shipment->fresh()->load(['order', 'shippingMethod', 'originMarket', 'destinationMarket']));
    }
}
