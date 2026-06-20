<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code', 'name', 'market_id', 'supplier_id', 'type', 'address', 'city', 'state',
    'postal_code', 'country_code', 'contact_person', 'phone', 'email',
    'capacity', 'used_capacity', 'is_active', 'remark',
])]
class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'used_capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class, 'warehouse_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'origin_warehouse_id');
    }

    public function incomingShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'destination_warehouse_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isPlatform()) {
            return $query;
        }

        if ($user->isSupplier()) {
            return $query->where('supplier_id', $user->supplier_id);
        }

        return $query->whereRaw('1=0');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByMarket(Builder $query, $marketId): Builder
    {
        return $query->where('market_id', $marketId);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function isOversea(): bool
    {
        return $this->type === 'oversea';
    }

    public function isDomestic(): bool
    {
        return $this->type === 'domestic';
    }

    public function isBonded(): bool
    {
        return $this->type === 'bonded';
    }

    public function usageRate(): float
    {
        if ($this->capacity <= 0) {
            return 0;
        }

        return round(($this->used_capacity / $this->capacity) * 100, 2);
    }
}
