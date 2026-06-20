<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::with('permissions')->orderBy('id', 'asc')->get();

        return response()->json(['roles' => $roles]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'unique:roles,name'],
            'guard_name' => ['sometimes', 'string', 'default:web'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web',
        ]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json([
            'message' => '角色创建成功',
            'role' => $role->load('permissions'),
        ], 201);
    }

    public function show(Request $request, Role $role)
    {
        return response()->json(['role' => $role->load('permissions')]);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'unique:roles,name,' . $role->id],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if (isset($validated['name'])) {
            $role->name = $validated['name'];
            $role->save();
        }

        if (array_key_exists('permissions', $validated)) {
            $role->syncPermissions($validated['permissions'] ?? []);
        }

        return response()->json([
            'message' => '角色更新成功',
            'role' => $role->load('permissions'),
        ]);
    }

    public function destroy(Request $request, Role $role)
    {
        if (in_array($role->name, ['platform', 'supplier', 'distributor', 'regional_agent'])) {
            return response()->json(['message' => '系统角色不能删除'], 422);
        }

        $role->delete();

        return response()->json(['message' => '角色删除成功']);
    }

    public function permissions(Request $request)
    {
        $permissions = Permission::orderBy('name', 'asc')->get();

        $grouped = [];
        foreach ($permissions as $perm) {
            $parts = explode('.', $perm->name);
            $group = $parts[0] ?? 'other';
            if (! isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][] = $perm;
        }

        return response()->json([
            'permissions' => $permissions,
            'grouped' => $grouped,
        ]);
    }
}
