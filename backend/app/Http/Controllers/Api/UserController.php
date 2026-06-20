<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with(['roles', 'supplier:id,name', 'distributor:id,name,type']);

        $this->applySearch($query, $request, ['name', 'email', 'phone']);

        if ($request->filled('user_type')) {
            $query->where('user_type', $request->string('user_type'));
        }

        if ($request->filled('role')) {
            $query->role($request->string('role'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $this->boolean($request, 'is_active'));
        }

        return UserResource::collection(
            $query->latest()->paginate($this->perPage($request))
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'user_type' => ['required', 'in:platform,supplier,distributor'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'distributor_id' => ['nullable', 'exists:distributors,id'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'user_type' => $validated['user_type'],
            'supplier_id' => $validated['supplier_id'] ?? null,
            'distributor_id' => $validated['distributor_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (! empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return new UserResource($user->load(['roles', 'supplier', 'distributor']));
    }

    public function show(Request $request, User $user)
    {
        return new UserResource($user->load(['roles', 'supplier', 'distributor']));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'user_type' => ['sometimes', 'required', 'in:platform,supplier,distributor'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'distributor_id' => ['nullable', 'exists:distributors,id'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $updateData = [
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
            'phone' => array_key_exists('phone', $validated) ? $validated['phone'] : $user->phone,
            'user_type' => $validated['user_type'] ?? $user->user_type,
            'supplier_id' => array_key_exists('supplier_id', $validated) ? $validated['supplier_id'] : $user->supplier_id,
            'distributor_id' => array_key_exists('distributor_id', $validated) ? $validated['distributor_id'] : $user->distributor_id,
            'is_active' => array_key_exists('is_active', $validated) ? $validated['is_active'] : $user->is_active,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = $validated['password'];
        }

        $user->update($updateData);

        if (array_key_exists('roles', $validated)) {
            $user->syncRoles($validated['roles'] ?? []);
        }

        return new UserResource($user->load(['roles', 'supplier', 'distributor']));
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => '不能删除当前登录账号'], 422);
        }

        $user->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function toggleStatus(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => '不能禁用当前登录账号'], 422);
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        return response()->json([
            'message' => '状态更新成功',
            'user' => new UserResource($user->load(['roles', 'supplier', 'distributor'])),
        ]);
    }
}
