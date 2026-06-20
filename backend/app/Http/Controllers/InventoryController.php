<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryRequest;
use App\Http\Resources\InventoryResource;
use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:inventory.view')->only(['index', 'show']);
        $this->middleware('permission:inventory.edit')->only(['store', 'update']);
        $this->middleware('permission:inventory.edit')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = Inventory::visibleTo($request->user())
            ->with(['product:id,name,sku,specification,unit', 'supplier:id,name']);

        $this->applySearch($query, $request, ['batch_no', 'location']);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        return InventoryResource::collection(
            $query->latest()->paginate($this->perPage($request))
        );
    }

    public function store(InventoryRequest $request)
    {
        $user = $request->user();

        if ($user->isPlatform()) {
            return response()->json(['message' => '平台不干预商家经营，库存由供应商自行维护'], 403);
        }

        $data = $request->validated();

        if ($user->isSupplier()) {
            $data['supplier_id'] = $user->supplier_id;
        }

        if (! isset($data['available_quantity'])) {
            $data['available_quantity'] = $data['quantity'];
        }

        $inventory = Inventory::create($data);

        return new InventoryResource($inventory->load(['product', 'supplier']));
    }

    public function show(Request $request, Inventory $inventory)
    {
        Inventory::visibleTo($request->user())->where('id', $inventory->id)->firstOrFail();

        return new InventoryResource($inventory->load(['product', 'supplier']));
    }

    public function update(InventoryRequest $request, Inventory $inventory)
    {
        $user = $request->user();

        if ($user->isPlatform()) {
            return response()->json(['message' => '平台不干预商家经营，库存由供应商自行维护'], 403);
        }

        Inventory::visibleTo($request->user())->where('id', $inventory->id)->firstOrFail();

        $data = $request->validated();

        if ($user->isSupplier()) {
            $data['supplier_id'] = $user->supplier_id;
        }

        $inventory->update($data);

        return new InventoryResource($inventory->load(['product', 'supplier']));
    }

    public function destroy(Request $request, Inventory $inventory)
    {
        Inventory::visibleTo($request->user())->where('id', $inventory->id)->firstOrFail();

        $inventory->delete();

        return response()->json(['message' => '删除成功']);
    }
}
