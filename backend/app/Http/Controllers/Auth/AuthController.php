<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => '账号或密码错误'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => '账号已被禁用，请联系管理员'], 403);
        }

        $user->load(['roles', 'supplier', 'distributor']);

        $token = $user->createToken('shearerline-web')->plainTextToken;

        return response()->json([
            'message' => '登录成功',
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => '已退出登录']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['roles', 'supplier', 'distributor']);

        return new UserResource($user);
    }
}
