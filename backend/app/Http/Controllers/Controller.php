<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class Controller
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
}
