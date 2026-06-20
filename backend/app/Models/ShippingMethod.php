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
    'code', 'name', 'carrier', 'origin_market_id', 'destination_market_id',
    'type', 'min_days', 'max_days', 'base_price', 'price_per_kg',
    'price_per_cbm', 'fuel_surcharge_rate', 'is_trackable',
    'is_active', 'sort', 'remark',
])]
class ShippingMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'min_days' => 'integer',
            'max_days' => 'integer',
            'base_price' => 'decimal:2',
            'price_per_kg' => 'decimal:2',
            'price_per_cbm' => 'decimal:2',
            'fuel_surcharge_rate' => 'decimal:4',
            'is_trackable' => 'boolean',
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function originMarket(): BelongsTo
    {
        return $this->belongsTo(Market::class, 'origin_market_id');
    }

    public function destinationMarket(): BelongsTo
    {
        return $this->belongsTo(Market::class, 'destination_market_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('name');
    }

    public function scopeByRoute(Builder $query, $originId, $destinationId): Builder
    {
        return $query->where('origin_market_id', $originId)
            ->where('destination_market_id', $destinationId);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function isAir(): bool
    {
        return $this->type === 'air';
    }

    public function isSea(): bool
    {
        return $this->type === 'sea';
    }

    public function isExpress(): bool
    {
        return $this->type === 'express';
    }

    public function isLand(): bool
    {
        return $this->type === 'land';
    }

    public function calculateCost(float $weight = 0, float $volume = 0): float
    {
        $cost = $this->base_price;

        if ($weight > 0 && $this->price_per_kg > 0) {
            $cost += $weight * $this->price_per_kg;
        }

        if ($volume > 0 && $this->price_per_cbm > 0) {
            $cost += $volume * $this->price_per_cbm;
        }

        if ($this->fuel_surcharge_rate > 0) {
            $cost *= (1 + $this->fuel_surcharge_rate);
        }

        return round($cost, 2);
    }
}
