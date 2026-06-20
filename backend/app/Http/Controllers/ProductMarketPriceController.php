<?php

namespace App\Http\Controllers;

use App\Models\ProductMarketPrice;
use Illuminate\Http\Request;

class ProductMarketPriceController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductMarketPrice::with(['product', 'market']);

        if ($request->filled('is_active')) {
            $query->where('is_active', $this->boolean($request, 'is_active'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        if ($request->filled('market_id')) {
            $query->where('market_id', $request->integer('market_id'));
        }

        return response()->json(
            $query->latest()->paginate($this->perPage($request))
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'market_id' => 'required|exists:markets,id',
            'currency' => 'required|string|max:10',
            'local_name' => 'nullable|string|max:255',
            'cost_price' => 'nullable|decimal:0,2',
            'wholesale_price' => 'nullable|decimal:0,2',
            'agent_price' => 'nullable|decimal:0,2',
            'retail_price' => 'nullable|decimal:0,2',
            'min_order_qty' => 'nullable|integer',
            'max_order_qty' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'remark' => 'nullable|string',
        ]);

        $price = ProductMarketPrice::create($validated);

        return response()->json($price->load(['product', 'market']));
    }

    public function show(Request $request, ProductMarketPrice $productMarketPrice)
    {
        return response()->json($productMarketPrice->load(['product', 'market']));
    }

    public function update(Request $request, ProductMarketPrice $productMarketPrice)
    {
        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'market_id' => 'sometimes|exists:markets,id',
            'currency' => 'sometimes|string|max:10',
            'local_name' => 'nullable|string|max:255',
            'cost_price' => 'nullable|decimal:0,2',
            'wholesale_price' => 'nullable|decimal:0,2',
            'agent_price' => 'nullable|decimal:0,2',
            'retail_price' => 'nullable|decimal:0,2',
            'min_order_qty' => 'nullable|integer',
            'max_order_qty' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'effective_date' => 'sometimes|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'remark' => 'nullable|string',
        ]);

        $productMarketPrice->update($validated);

        return response()->json($productMarketPrice->load(['product', 'market']));
    }

    public function destroy(Request $request, ProductMarketPrice $productMarketPrice)
    {
        $productMarketPrice->delete();

        return response()->json(['message' => '删除成功']);
    }
}
