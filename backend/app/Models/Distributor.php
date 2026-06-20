<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

#[Fillable([
    'name', 'company_name', 'business_license', 'type', 'region',
    'contact_person', 'phone', 'email', 'address', 'bank_name',
    'bank_account', 'credit_limit', 'balance', 'discount_rate',
    'status', 'parent_id', 'remark',
    'market_id', 'country_code', 'tax_id', 'local_business_license',
    'import_export_code', 'serviced_states', 'payment_terms',
    'shipping_preferences', 'is_cross_border', 'certifications',
])]
class Distributor extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'balance' => 'decimal:2',
            'discount_rate' => 'integer',
            'serviced_states' => 'array',
            'payment_terms' => 'array',
            'shipping_preferences' => 'array',
            'is_cross_border' => 'boolean',
            'certifications' => 'array',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Distributor::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Distributor::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function shipments(): HasManyThrough
    {
        return $this->hasManyThrough(Shipment::class, Order::class);
    }

    public function scopeCrossBorder(Builder $query): Builder
    {
        return $query->where('is_cross_border', true);
    }

    public function scopeByMarket(Builder $query, $marketId): Builder
    {
        return $query->where('market_id', $marketId);
    }

    public function isRegionalAgent(): bool
    {
        return $this->type === 'regional_agent';
    }

    public function isWholesaler(): bool
    {
        return $this->type === 'wholesaler';
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

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isPlatform()) {
            return $query;
        }

        if ($user->isDistributor() && $user->distributor_id) {
            $ids = [$user->distributor_id];

            if ($user->isRegionalAgent()) {
                $distributor = static::find($user->distributor_id);

                if ($distributor) {
                    $ids = array_merge($ids, $distributor->descendantIds());
                }
            }

            return $query->whereIn('id', $ids);
        }

        return $query->whereRaw('1=0');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeRegionalAgents(Builder $query): Builder
    {
        return $query->where('type', 'regional_agent');
    }

    public function scopeWholesalers(Builder $query): Builder
    {
        return $query->where('type', 'wholesaler');
    }
}
