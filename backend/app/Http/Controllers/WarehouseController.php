<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::visibleTo($request->user())
            ->with(['market', 'supplier']);

        $this->applySearch($query, $request, ['code', 'name', 'city', 'contact_person', 'phone']);

        if ($request->filled('is_active')) {
            $query->where('is_active', $this->boolean($request, 'is_active'));
        }

        if ($request->filled('market_id')) {
            $query->where('market_id', $request->integer('market_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        return response()->json(
            $query->latest()->paginate($this->perPage($request))
        );
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouses,code',
            'name' => 'required|string|max:255',
            'market_id' => 'nullable|exists:markets,id',
            'supplier_id' => [$user->isSupplier() ? 'nullable' : 'required', 'exists:suppliers,id'],
            'type' => 'sometimes|in:domestic,oversea,bonded,transit',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'country_code' => 'nullable|string|max:10',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'capacity' => 'nullable|integer',
            'used_capacity' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'remark' => 'nullable|string',
        ]);

        if ($user->isSupplier()) {
            $validated['supplier_id'] = $user->supplier_id;
        }

        $warehouse = Warehouse::create($validated);

        return response()->json($warehouse->load(['market', 'supplier']));
    }

    public function show(Request $request, Warehouse $warehouse)
    {
        Warehouse::visibleTo($request->user())->where('id', $warehouse->id)->firstOrFail();

        return response()->json(
            $warehouse->load(['market', 'supplier', 'inventory.product'])
        );
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        Warehouse::visibleTo($request->user())->where('id', $warehouse->id)->firstOrFail();

        $user = $request->user();

        $validated = $request->validate([
            'code' => 'sometimes|string|max:50|unique:warehouses,code,' . $warehouse->id,
            'name' => 'sometimes|string|max:255',
            'market_id' => 'nullable|exists:markets,id',
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'type' => 'sometimes|in:domestic,oversea,bonded,transit',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'country_code' => 'nullable|string|max:10',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'capacity' => 'nullable|integer',
            'used_capacity' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'remark' => 'nullable|string',
        ]);

        if ($user->isSupplier()) {
            $validated['supplier_id'] = $user->supplier_id;
        }

        $warehouse->update($validated);

        return response()->json($warehouse->load(['market', 'supplier']));
    }

    public function destroy(Request $request, Warehouse $warehouse)
    {
        Warehouse::visibleTo($request->user())->where('id', $warehouse->id)->firstOrFail();

        $warehouse->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function toggleStatus(Request $request, Warehouse $warehouse)
    {
        Warehouse::visibleTo($request->user())->where('id', $warehouse->id)->firstOrFail();

        $warehouse->update(['is_active' => !$warehouse->is_active]);

        return response()->json($warehouse);
    }
}
