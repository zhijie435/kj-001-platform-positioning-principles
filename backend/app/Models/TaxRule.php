<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'market_id', 'category_id', 'type', 'name', 'rate',
    'min_amount', 'max_amount', 'is_compound', 'compound_rules',
    'effective_date', 'expiry_date', 'is_active', 'remark',
])]
class TaxRule extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'is_compound' => 'boolean',
            'compound_rules' => 'array',
            'effective_date' => 'date',
            'expiry_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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

    public function scopeByMarket(Builder $query, $marketId): Builder
    {
        return $query->where('market_id', $marketId);
    }

    public function scopeByCategory(Builder $query, $categoryId): Builder
    {
        return $query->where('category_id', $categoryId)
            ->orWhereNull('category_id');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function isVat(): bool
    {
        return $this->type === 'vat';
    }

    public function isGst(): bool
    {
        return $this->type === 'gst';
    }

    public function isSalesTax(): bool
    {
        return $this->type === 'sales_tax';
    }

    public function isDuty(): bool
    {
        return $this->type === 'duty';
    }

    public function isIpi(): bool
    {
        return $this->type === 'ipi';
    }

    public function isIcms(): bool
    {
        return $this->type === 'icms';
    }

    public function calculateTax(float $amount): float
    {
        if ($this->min_amount > 0 && $amount < $this->min_amount) {
            return 0;
        }

        if ($this->max_amount > 0 && $amount > $this->max_amount) {
            $amount = $this->max_amount;
        }

        if ($this->is_compound && !empty($this->compound_rules)) {
            return $this->calculateCompoundTax($amount);
        }

        return round($amount * $this->rate, 2);
    }

    protected function calculateCompoundTax(float $amount): float
    {
        $tax = 0;
        $remaining = $amount;

        foreach ($this->compound_rules as $rule) {
            $bracketMin = $rule['min'] ?? 0;
            $bracketMax = $rule['max'] ?? INF;
            $bracketRate = $rule['rate'] ?? 0;

            $taxableAmount = max(0, min($remaining, $bracketMax) - $bracketMin);
            $tax += $taxableAmount * $bracketRate;
            $remaining -= $taxableAmount;

            if ($remaining <= 0) {
                break;
            }
        }

        return round($tax, 2);
    }

    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return now()->toDateString() > $this->expiry_date;
    }
}
