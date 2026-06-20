<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::visibleTo($request->user())
            ->with(['category:id,name', 'supplier:id,name']);

        $this->applySearch($query, $request, ['name', 'sku', 'barcode']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        return ProductResource::collection(
            $query->latest()->paginate($this->perPage($request))
        );
    }

    public function store(ProductRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        if ($user->isSupplier()) {
            $data['supplier_id'] = $user->supplier_id;
        }

        $product = Product::create($data);

        return new ProductResource($product->load(['category', 'supplier']));
    }

    public function show(Request $request, Product $product)
    {
        Product::visibleTo($request->user())->where('id', $product->id)->firstOrFail();

        return new ProductResource($product->load(['category', 'supplier']));
    }

    public function update(ProductRequest $request, Product $product)
    {
        Product::visibleTo($request->user())->where('id', $product->id)->firstOrFail();

        $data = $request->validated();
        $user = $request->user();

        if ($user->isSupplier()) {
            $data['supplier_id'] = $user->supplier_id;
        }

        $product->update($data);

        return new ProductResource($product->load(['category', 'supplier']));
    }

    public function destroy(Request $request, Product $product)
    {
        Product::visibleTo($request->user())->where('id', $product->id)->firstOrFail();

        $product->delete();

        return response()->json(['message' => '删除成功']);
    }
}
