<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'product_id', 'market_id', 'currency', 'local_name',
    'cost_price', 'wholesale_price', 'agent_price', 'retail_price',
    'min_order_qty', 'max_order_qty', 'is_active', 'effective_date',
    'expiry_date', 'remark',
])]
class ProductMarketPrice extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'agent_price' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'min_order_qty' => 'integer',
            'max_order_qty' => 'integer',
            'is_active' => 'boolean',
            'effective_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('is_active', true)
            ->where('effective_date', '<=', $today)
            ->where(function (Builder $q) use ($today) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', $today);
            });
    }

    public function scopeByProduct(Builder $query, $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByMarket(Builder $query, $marketId): Builder
    {
        return $query->where('market_id', $marketId);
    }

    public function getPriceFor(string $type): ?float
    {
        return match ($type) {
            'cost' => $this->cost_price,
            'wholesale' => $this->wholesale_price,
            'agent' => $this->agent_price,
            'retail' => $this->retail_price,
            default => null,
        };
    }

    public function isPriceValid(int $quantity): bool
    {
        if ($this->min_order_qty > 0 && $quantity < $this->min_order_qty) {
            return false;
        }

        if ($this->max_order_qty > 0 && $quantity > $this->max_order_qty) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return now()->toDateString() > $this->expiry_date;
    }
}
