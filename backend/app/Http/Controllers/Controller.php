<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class Controller extends \Illuminate\Routing\Controller
{
    protected function perPage(Request $request): int
    {
        $perPage = (int) $request->integer('per_page', 15);

        return max(1, min($perPage, 100));
    }

    protected function applySearch(Builder $query, Request $request, array $columns): Builder
    {
        $keyword = $request->string('search')->toString();

        if ($keyword === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($columns, $keyword) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$keyword}%");
            }
        });
    }

    protected function boolean(Request $request, string $key, ?bool $default = null): ?bool
    {
        if (! $request->has($key)) {
            return $default;
        }

        $value = $request->string($key)->toString();

        if (in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        if (in_array(strtolower($value), ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return $default;
    }

    protected function success(mixed $data = null, string $message = '操作成功', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error(string $message = '操作失败', string $errorCode = 'ERROR', array $details = [], int $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'details' => $details,
        ], $code);
    }
}
