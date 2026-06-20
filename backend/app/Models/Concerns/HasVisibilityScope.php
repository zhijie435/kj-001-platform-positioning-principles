<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasVisibilityScope
{
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isPlatform()) {
            return $query;
        }

        $strategy = $this->resolveVisibilityStrategy($user);

        return $strategy ? $strategy($query, $user) : $query->whereRaw('1=0');
    }

    protected function resolveVisibilityStrategy(User $user): ?callable
    {
        $visibilityMap = $this->visibilityMap ?? [];

        foreach ($visibilityMap as $userType => $config) {
            if ($this->checkUserType($user, $userType)) {
                return $this->buildStrategy($config, $user);
            }
        }

        return null;
    }

    protected function checkUserType(User $user, string $type): bool
    {
        return match ($type) {
            'supplier' => $user->isSupplier(),
            'distributor' => $user->isDistributor() && $user->distributor_id,
            'regional_agent' => $user->isRegionalAgent(),
            'wholesaler' => $user->isWholesaler(),
            default => false,
        };
    }

    protected function buildStrategy(array $config, User $user): callable
    {
        return function (Builder $query) use ($config, $user) {
            $foreignKey = $config['foreign_key'] ?? null;
            $relation = $config['relation'] ?? null;
            $includeDescendants = $config['include_descendants'] ?? false;

            if ($foreignKey && !$relation) {
                return $this->applyDirectScope($query, $user, $foreignKey);
            }

            if ($relation) {
                return $this->applyRelationScope($query, $user, $config, $includeDescendants);
            }

            return $query->whereRaw('1=0');
        };
    }

    protected function applyDirectScope(Builder $query, User $user, string $foreignKey): Builder
    {
        if ($foreignKey === 'supplier_id') {
            return $query->where('supplier_id', $user->supplier_id);
        }

        if ($foreignKey === 'id') {
            return $query->where('id', $user->distributor_id);
        }

        if ($foreignKey === 'distributor_id') {
            $ids = [$user->distributor_id];

            if ($user->isRegionalAgent() && $user->distributor) {
                $ids = array_merge($ids, $user->distributor->descendantIds());
            }

            return $query->whereIn('distributor_id', $ids);
        }

        return $query->whereRaw('1=0');
    }

    protected function applyRelationScope(Builder $query, User $user, array $config, bool $includeDescendants): Builder
    {
        $relation = $config['relation'];
        $foreignKey = $config['foreign_key'] ?? null;

        return $query->whereHas($relation, function (Builder $q) use ($user, $foreignKey, $includeDescendants) {
            if ($user->isSupplier()) {
                $q->where($foreignKey ?? 'supplier_id', $user->supplier_id);
            } elseif ($user->isDistributor() && $user->distributor_id) {
                $ids = [$user->distributor_id];

                if ($includeDescendants && $user->isRegionalAgent() && $user->distributor) {
                    $ids = array_merge($ids, $user->distributor->descendantIds());
                }

                $q->whereIn($foreignKey ?? 'distributor_id', $ids);
            } else {
                $q->whereRaw('1=0');
            }
        });
    }
}
