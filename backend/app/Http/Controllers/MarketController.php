<?php

namespace App\Http\Controllers;

use App\Models\Market;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function index(Request $request)
    {
        $query = Market::withCount(['warehouses', 'productPrices']);

        $this->applySearch($query, $request, ['code', 'name', 'name_en', 'country_code', 'currency_code']);

        if ($request->filled('is_active')) {
            $query->where('is_active', $this->boolean($request, 'is_active'));
        }

        if ($request->filled('country_code')) {
            $query->where('country_code', $request->string('country_code'));
        }

        return response()->json(
            $query->ordered()->paginate($this->perPage($request))
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:markets,code',
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'country_code' => 'required|string|max:10',
            'currency_code' => 'required|string|max:10',
            'currency_symbol' => 'nullable|string|max:10',
            'language_code' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
            'flag' => 'nullable|string|max:255',
            'tax_rate' => 'nullable|decimal:0,4',
            'is_active' => 'nullable|boolean',
            'sort' => 'nullable|integer',
            'remark' => 'nullable|string',
        ]);

        $market = Market::create($validated);

        return response()->json($market);
    }

    public function show(Request $request, Market $market)
    {
        return response()->json(
            $market->loadCount(['warehouses', 'productPrices', 'taxRules', 'shipments', 'orders'])
        );
    }

    public function update(Request $request, Market $market)
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:50|unique:markets,code,' . $market->id,
            'name' => 'sometimes|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'country_code' => 'sometimes|string|max:10',
            'currency_code' => 'sometimes|string|max:10',
            'currency_symbol' => 'nullable|string|max:10',
            'language_code' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
            'flag' => 'nullable|string|max:255',
            'tax_rate' => 'nullable|decimal:0,4',
            'is_active' => 'nullable|boolean',
            'sort' => 'nullable|integer',
            'remark' => 'nullable|string',
        ]);

        $market->update($validated);

        return response()->json($market);
    }

    public function destroy(Request $request, Market $market)
    {
        $market->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function toggleStatus(Market $market)
    {
        $market->update(['is_active' => !$market->is_active]);

        return response()->json($market);
    }
}
