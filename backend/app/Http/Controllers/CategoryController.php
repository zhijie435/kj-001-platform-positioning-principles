<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query()->withCount('products');

        if ($request->boolean('active')) {
            $query->active();
        }

        if ($request->filled('parent_id')) {
            $parentId = $request->input('parent_id');
            if ($parentId === 'null') {
                $query->root();
            } else {
                $query->where('parent_id', (int) $parentId);
            }
        }

        $this->applySearch($query, $request, ['name', 'code']);

        return CategoryResource::collection($query->ordered()->paginate($this->perPage($request)));
    }

    public function tree(Request $request)
    {
        $categories = Category::with(['children' => function ($q) {
            $q->ordered()->withCount('products');
        }])
            ->withCount('products')
            ->root()
            ->ordered()
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(CategoryRequest $request)
    {
        $category = Category::create($request->validated());

        return new CategoryResource($category);
    }

    public function show(Request $request, Category $category)
    {
        return new CategoryResource($category->loadCount('products'));
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $data = $request->validated();

        if (isset($data['parent_id']) && (int) $data['parent_id'] === $category->id) {
            unset($data['parent_id']);
        }

        $category->update($data);

        return new CategoryResource($category);
    }

    public function destroy(Request $request, Category $category)
    {
        $category->delete();

        return response()->json(['message' => '删除成功']);
    }
}
