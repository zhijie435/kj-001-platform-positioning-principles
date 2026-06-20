<?php

namespace App\Http\Controllers;

use App\Models\CustomerGroup;
use Illuminate\Http\Request;

class CustomerGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerGroup::visibleTo($request->user())
            ->with(['market', 'parent'])
            ->withCount(['users', 'distributors']);

        $this->applySearch($query, $request, ['name', 'code', 'description']);

        if ($request->filled('is_active')) {
            $query->where('is_active', $this->boolean($request, 'is_active'));
        }

        if ($request->filled('market_id')) {
            $query->where('market_id', $request->integer('market_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('parent_id')) {
            $parentId = $request->integer('parent_id');
            if ($parentId === 0) {
                $query->root();
            } else {
                $query->where('parent_id', $parentId);
            }
        }

        return response()->json(
            $query->ordered()->paginate($this->perPage($request))
        );
    }

    public function tree(Request $request)
    {
        $query = CustomerGroup::visibleTo($request->user())
            ->with(['market'])
            ->withCount(['users', 'distributors'])
            ->active()
            ->ordered();

        if ($request->filled('market_id')) {
            $query->where('market_id', $request->integer('market_id'));
        }

        $groups = $query->get();
        $groupTree = $this->buildTree($groups);

        return response()->json($groupTree);
    }

    protected function buildTree($groups, $parentId = null): array
    {
        $tree = [];

        foreach ($groups as $group) {
            if ($group->parent_id == $parentId) {
                $children = $this->buildTree($groups, $group->id);
                $node = $group->toArray();
                if (!empty($children)) {
                    $node['children'] = $children;
                }
                $tree[] = $node;
            }
        }

        return $tree;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:customer_groups,code',
            'parent_id' => 'nullable|exists:customer_groups,id',
            'market_id' => 'nullable|exists:markets,id',
            'type' => 'sometimes|in:normal,vip,wholesale,agent,enterprise',
            'level' => 'nullable|integer',
            'discount_rate' => 'nullable|integer|min:0|max:100',
            'credit_limit' => 'nullable|decimal:0,2',
            'description' => 'nullable|string',
            'rules' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'sort' => 'nullable|integer',
            'remark' => 'nullable|string',
        ]);

        $group = CustomerGroup::create($validated);

        return response()->json($group->load(['market', 'parent']));
    }

    public function show(Request $request, CustomerGroup $customerGroup)
    {
        CustomerGroup::visibleTo($request->user())->where('id', $customerGroup->id)->firstOrFail();

        return response()->json(
            $customerGroup->load(['market', 'parent', 'children', 'users', 'distributors'])
                ->loadCount(['users', 'distributors'])
        );
    }

    public function update(Request $request, CustomerGroup $customerGroup)
    {
        CustomerGroup::visibleTo($request->user())->where('id', $customerGroup->id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:customer_groups,code,' . $customerGroup->id,
            'parent_id' => 'nullable|exists:customer_groups,id',
            'market_id' => 'nullable|exists:markets,id',
            'type' => 'sometimes|in:normal,vip,wholesale,agent,enterprise',
            'level' => 'nullable|integer',
            'discount_rate' => 'nullable|integer|min:0|max:100',
            'credit_limit' => 'nullable|decimal:0,2',
            'description' => 'nullable|string',
            'rules' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'sort' => 'nullable|integer',
            'remark' => 'nullable|string',
        ]);

        if (isset($validated['parent_id']) && $validated['parent_id'] == $customerGroup->id) {
            return response()->json(['message' => '父级分组不能为自身'], 422);
        }

        $customerGroup->update($validated);

        return response()->json($customerGroup->load(['market', 'parent']));
    }

    public function destroy(Request $request, CustomerGroup $customerGroup)
    {
        CustomerGroup::visibleTo($request->user())->where('id', $customerGroup->id)->firstOrFail();

        if ($customerGroup->children()->exists()) {
            return response()->json(['message' => '该分组下存在子分组，无法删除'], 422);
        }

        if ($customerGroup->users()->exists() || $customerGroup->distributors()->exists()) {
            return response()->json(['message' => '该分组下存在成员，无法删除'], 422);
        }

        $customerGroup->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function toggleStatus(Request $request, CustomerGroup $customerGroup)
    {
        CustomerGroup::visibleTo($request->user())->where('id', $customerGroup->id)->firstOrFail();

        $customerGroup->update(['is_active' => !$customerGroup->is_active]);

        return response()->json($customerGroup);
    }

    public function attachUsers(Request $request, CustomerGroup $customerGroup)
    {
        CustomerGroup::visibleTo($request->user())->where('id', $customerGroup->id)->firstOrFail();

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $customerGroup->users()->syncWithoutDetaching($validated['user_ids']);

        return response()->json([
            'message' => '添加成功',
            'attached_count' => count($validated['user_ids']),
        ]);
    }

    public function detachUsers(Request $request, CustomerGroup $customerGroup)
    {
        CustomerGroup::visibleTo($request->user())->where('id', $customerGroup->id)->firstOrFail();

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $customerGroup->users()->detach($validated['user_ids']);

        return response()->json([
            'message' => '移除成功',
            'detached_count' => count($validated['user_ids']),
        ]);
    }

    public function attachDistributors(Request $request, CustomerGroup $customerGroup)
    {
        CustomerGroup::visibleTo($request->user())->where('id', $customerGroup->id)->firstOrFail();

        $validated = $request->validate([
            'distributor_ids' => 'required|array',
            'distributor_ids.*' => 'exists:distributors,id',
        ]);

        $customerGroup->distributors()->syncWithoutDetaching($validated['distributor_ids']);

        return response()->json([
            'message' => '添加成功',
            'attached_count' => count($validated['distributor_ids']),
        ]);
    }

    public function detachDistributors(Request $request, CustomerGroup $customerGroup)
    {
        CustomerGroup::visibleTo($request->user())->where('id', $customerGroup->id)->firstOrFail();

        $validated = $request->validate([
            'distributor_ids' => 'required|array',
            'distributor_ids.*' => 'exists:distributors,id',
        ]);

        $customerGroup->distributors()->detach($validated['distributor_ids']);

        return response()->json([
            'message' => '移除成功',
            'detached_count' => count($validated['distributor_ids']),
        ]);
    }
}
