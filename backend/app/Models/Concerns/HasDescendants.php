<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

trait HasDescendants
{
    protected static int $descendantCacheTtl = 3600;

    public function descendantIds(): array
    {
        $cacheKey = $this->getDescendantCacheKey();

        return Cache::remember($cacheKey, static::$descendantCacheTtl, function () {
            return $this->loadDescendantIds();
        });
    }

    public function flushDescendantCache(): void
    {
        $parent = $this;

        while ($parent) {
            Cache::forget($parent->getDescendantCacheKey());
            $parent = $parent->parent;
        }
    }

    protected function getDescendantCacheKey(): string
    {
        return sprintf('%s:%s:descendants', static::class, $this->getKey());
    }

    protected function loadDescendantIds(): array
    {
        $all = static::query()
            ->select(['id', 'parent_id'])
            ->get()
            ->keyBy('id');

        return $this->collectDescendantIds($all, $this->getKey());
    }

    protected function collectDescendantIds(Collection $all, int $parentId): array
    {
        $ids = [];
        $children = $all->where('parent_id', $parentId);

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->collectDescendantIds($all, $child->id));
        }

        return array_values(array_unique($ids));
    }

    public function scopeWithDescendants(Builder $query, int $parentId): Builder
    {
        $instance = new static();
        $ids = $instance->newInstance()->findOrFail($parentId)->descendantIds();
        $ids[] = $parentId;

        return $query->whereIn('id', $ids);
    }

    protected static function bootHasDescendants(): void
    {
        static::saved(function ($model) {
            if (method_exists($model, 'flushDescendantCache')) {
                $model->flushDescendantCache();
            }
        });

        static::deleted(function ($model) {
            if (method_exists($model, 'flushDescendantCache')) {
                $model->flushDescendantCache();
            }
        });
    }
}
