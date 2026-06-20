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
    'name', 'sku', 'barcode', 'category_id', 'supplier_id',
    'specification', 'unit', 'cost_price', 'wholesale_price',
    'retail_price', 'agent_price', 'stock_quantity', 'safety_stock',
    'description', 'images', 'status',
    'hs_code', 'country_of_origin', 'weight', 'volume',
    'is_cross_border', 'material', 'brand', 'certifications',
    'customs_description', 'local_names',
])]
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'agent_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'safety_stock' => 'integer',
            'images' => 'array',
            'weight' => 'decimal:3',
            'volume' => 'decimal:3',
            'is_cross_border' => 'boolean',
            'certifications' => 'array',
            'local_names' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function marketPrices(): HasMany
    {
        return $this->hasMany(ProductMarketPrice::class);
    }

    public function scopeCrossBorder(Builder $query): Builder
    {
        return $query->where('is_cross_border', true);
    }

    public function getMarketPrice($marketId, string $type = 'retail'): ?float
    {
        $price = $this->marketPrices()
            ->where('market_id', $marketId)
            ->active()
            ->first();

        return $price?->getPriceFor($type);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isPlatform()) {
            return $query;
        }

        if ($user->isSupplier()) {
            return $query->where('supplier_id', $user->supplier_id);
        }

        return $query->onSale();
    }

    public function scopeOnSale(Builder $query): Builder
    {
        return $query->where('status', 'on_sale');
    }

    public function scopeOfSupplier(Builder $query, $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeOfCategory(Builder $query, $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function isOnSale(): bool
    {
        return $this->status === 'on_sale';
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->safety_stock;
    }
}
