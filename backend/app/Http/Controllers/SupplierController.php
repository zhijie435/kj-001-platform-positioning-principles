<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::visibleTo($request->user())
            ->withCount('products');

        $this->applySearch($query, $request, ['name', 'company_name', 'contact_person', 'phone']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return SupplierResource::collection(
            $query->latest()->paginate($this->perPage($request))
        );
    }

    public function store(SupplierRequest $request)
    {
        $supplier = Supplier::create($request->validated());

        return new SupplierResource($supplier);
    }

    public function show(Request $request, Supplier $supplier)
    {
        Supplier::visibleTo($request->user())->where('id', $supplier->id)->firstOrFail();

        return new SupplierResource($supplier->loadCount('products'));
    }

    public function update(SupplierRequest $request, Supplier $supplier)
    {
        Supplier::visibleTo($request->user())->where('id', $supplier->id)->firstOrFail();

        $supplier->update($request->validated());

        return new SupplierResource($supplier);
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        Supplier::visibleTo($request->user())->where('id', $supplier->id)->firstOrFail();

        $supplier->delete();

        return response()->json(['message' => '删除成功']);
    }
}
