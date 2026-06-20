<?php

namespace App\Http\Controllers;

use App\Models\ShippingMethod;
use Illuminate\Http\Request;

class ShippingMethodController extends Controller
{
    public function index(Request $request)
    {
        $query = ShippingMethod::with(['originMarket', 'destinationMarket']);

        $this->applySearch($query, $request, ['code', 'name', 'carrier']);

        if ($request->filled('is_active')) {
            $query->where('is_active', $this->boolean($request, 'is_active'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('origin_market_id') && $request->filled('destination_market_id')) {
            $query->byRoute(
                $request->integer('origin_market_id'),
                $request->integer('destination_market_id')
            );
        }

        return response()->json(
            $query->ordered()->paginate($this->perPage($request))
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:shipping_methods,code',
            'name' => 'required|string|max:255',
            'carrier' => 'nullable|string|max:255',
            'origin_market_id' => 'nullable|exists:markets,id',
            'destination_market_id' => 'nullable|exists:markets,id',
            'type' => 'sometimes|in:air,sea,express,land,rail',
            'min_days' => 'nullable|integer',
            'max_days' => 'nullable|integer',
            'base_price' => 'nullable|decimal:0,2',
            'price_per_kg' => 'nullable|decimal:0,2',
            'price_per_cbm' => 'nullable|decimal:0,2',
            'fuel_surcharge_rate' => 'nullable|decimal:0,4',
            'is_trackable' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort' => 'nullable|integer',
            'remark' => 'nullable|string',
        ]);

        $method = ShippingMethod::create($validated);

        return response()->json($method->load(['originMarket', 'destinationMarket']));
    }

    public function show(Request $request, ShippingMethod $shippingMethod)
    {
        return response()->json(
            $shippingMethod->load(['originMarket', 'destinationMarket'])
        );
    }

    public function update(Request $request, ShippingMethod $shippingMethod)
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:50|unique:shipping_methods,code,' . $shippingMethod->id,
            'name' => 'sometimes|string|max:255',
            'carrier' => 'nullable|string|max:255',
            'origin_market_id' => 'nullable|exists:markets,id',
            'destination_market_id' => 'nullable|exists:markets,id',
            'type' => 'sometimes|in:air,sea,express,land,rail',
            'min_days' => 'nullable|integer',
            'max_days' => 'nullable|integer',
            'base_price' => 'nullable|decimal:0,2',
            'price_per_kg' => 'nullable|decimal:0,2',
            'price_per_cbm' => 'nullable|decimal:0,2',
            'fuel_surcharge_rate' => 'nullable|decimal:0,4',
            'is_trackable' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort' => 'nullable|integer',
            'remark' => 'nullable|string',
        ]);

        $shippingMethod->update($validated);

        return response()->json($shippingMethod->load(['originMarket', 'destinationMarket']));
    }

    public function destroy(Request $request, ShippingMethod $shippingMethod)
    {
        $shippingMethod->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function calculate(Request $request, ShippingMethod $shippingMethod)
    {
        $validated = $request->validate([
            'weight' => 'required|decimal:0,3',
            'volume' => 'required|decimal:0,3',
        ]);

        $cost = $shippingMethod->calculateCost(
            (float) $validated['weight'],
            (float) $validated['volume']
        );

        return response()->json([
            'shipping_method' => $shippingMethod,
            'weight' => (float) $validated['weight'],
            'volume' => (float) $validated['volume'],
            'estimated_cost' => $cost,
        ]);
    }
}
