<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code', 'name', 'name_en', 'country_code', 'currency_code',
    'currency_symbol', 'language_code', 'timezone', 'flag',
    'tax_rate', 'is_active', 'sort', 'remark',
])]
class Market extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:4',
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductMarketPrice::class);
    }

    public function taxRules(): HasMany
    {
        return $this->hasMany(TaxRule::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'destination_market_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'market_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('name');
    }

    public function scopeByCountry(Builder $query, string $countryCode): Builder
    {
        return $query->where('country_code', $countryCode);
    }

    public function isUS(): bool
    {
        return $this->country_code === 'US';
    }

    public function isBR(): bool
    {
        return $this->country_code === 'BR';
    }
}
