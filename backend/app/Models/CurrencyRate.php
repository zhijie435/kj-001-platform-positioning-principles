<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'base_currency', 'target_currency', 'rate', 'buy_rate', 'sell_rate',
    'source', 'effective_date', 'expiry_date', 'remark',
])]
class CurrencyRate extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'buy_rate' => 'decimal:6',
            'sell_rate' => 'decimal:6',
            'effective_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('effective_date', '<=', $today)
            ->where(function (Builder $q) use ($today) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', $today);
            });
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('effective_date', 'desc');
    }

    public function scopeByPair(Builder $query, string $base, string $target): Builder
    {
        return $query->where('base_currency', $base)
            ->where('target_currency', $target);
    }

    public function convert(float $amount): float
    {
        return round($amount * $this->rate, 2);
    }

    public function convertReverse(float $amount): float
    {
        if ($this->rate <= 0) {
            return 0;
        }

        return round($amount / $this->rate, 2);
    }

    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return now()->toDateString() > $this->expiry_date;
    }
}
