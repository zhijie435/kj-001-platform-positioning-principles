<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;

#[Fillable([
    'name', 'code', 'parent_id', 'market_id', 'type', 'level',
    'discount_rate', 'credit_limit', 'description', 'rules',
    'is_active', 'sort', 'remark',
])]
class CustomerGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'discount_rate' => 'integer',
            'credit_limit' => 'decimal:2',
            'level' => 'integer',
            'rules' => 'array',
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(Config::get('customer_groups.table_names.customer_groups', 'customer_groups'));
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            Config::get('customer_groups.models.customer', User::class),
            'model',
            Config::get('customer_groups.table_names.model_has_customer_groups', 'model_has_customer_groups'),
            Config::get('customer_groups.column_names.customer_group_pivot_key', 'customer_group_id'),
            Config::get('customer_groups.column_names.model_morph_key', 'model_id')
        );
    }

    public function distributors(): MorphToMany
    {
        return $this->morphedByMany(
            Distributor::class,
            'model',
            Config::get('customer_groups.table_names.model_has_customer_groups', 'model_has_customer_groups'),
            Config::get('customer_groups.column_names.customer_group_pivot_key', 'customer_group_id'),
            Config::get('customer_groups.column_names.model_morph_key', 'model_id')
        );
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isPlatform()) {
            return $query;
        }

        if ($user->isSupplier()) {
            return $query;
        }

        if ($user->isDistributor() && $user->distributor_id) {
            return $query;
        }

        return $query->whereRaw('1=0');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('name');
    }

    public function scopeByMarket(Builder $query, $marketId): Builder
    {
        return $query->where('market_id', $marketId);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function isVip(): bool
    {
        return $this->type === 'vip';
    }

    public function isNormal(): bool
    {
        return $this->type === 'normal';
    }

    public function isWholesale(): bool
    {
        return $this->type === 'wholesale';
    }

    public function isAgent(): bool
    {
        return $this->type === 'agent';
    }

    public function descendantIds(): array
    {
        $ids = [];
        $children = $this->children()->pluck('id')->all();

        foreach ($children as $childId) {
            $ids[] = $childId;
            $child = static::find($childId);

            if ($child) {
                $ids = array_merge($ids, $child->descendantIds());
            }
        }

        return array_values(array_unique($ids));
    }

    public function descendantAndSelfIds(): array
    {
        return array_merge([$this->id], $this->descendantIds());
    }

    public function applyDiscount(float $amount): float
    {
        if ($this->discount_rate <= 0) {
            return $amount;
        }

        return round($amount * (1 - $this->discount_rate / 100), 2);
    }
}
