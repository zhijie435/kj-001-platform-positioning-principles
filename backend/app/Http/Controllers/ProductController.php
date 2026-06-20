<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:product.view')->only(['index', 'show']);
        $this->middleware('permission:product.create')->only(['store']);
        $this->middleware('permission:product.edit')->only(['update']);
        $this->middleware('permission:product.delete')->only(['destroy']);
    }

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
        $user = $request->user();

        if ($user->isPlatform()) {
            return response()->json(['message' => '平台不参与自营，产品由供应商自行发布'], 403);
        }

        $data = $request->validated();

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
        $user = $request->user();

        if ($user->isPlatform()) {
            return response()->json(['message' => '平台不参与自营，产品由供应商自行维护'], 403);
        }

        Product::visibleTo($request->user())->where('id', $product->id)->firstOrFail();

        $data = $request->validated();

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
