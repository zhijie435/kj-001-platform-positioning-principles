<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customs_declaration_id', 'product_id', 'product_name', 'product_sku',
    'hs_code', 'country_of_origin', 'quantity', 'unit',
    'unit_value', 'total_value', 'currency', 'weight_per_unit',
    'gross_weight', 'net_weight', 'duty_rate', 'duty_amount',
    'vat_rate', 'vat_amount', 'remark',
])]
class CustomsDeclarationItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_value' => 'decimal:2',
            'total_value' => 'decimal:2',
            'weight_per_unit' => 'decimal:3',
            'gross_weight' => 'decimal:3',
            'net_weight' => 'decimal:3',
            'duty_rate' => 'decimal:4',
            'duty_amount' => 'decimal:2',
            'vat_rate' => 'decimal:4',
            'vat_amount' => 'decimal:2',
        ];
    }

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(CustomsDeclaration::class, 'customs_declaration_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function calculateDuty(): float
    {
        return round($this->total_value * $this->duty_rate, 2);
    }

    public function calculateVat(float $baseAmount = null): float
    {
        $base = $baseAmount ?? ($this->total_value + $this->calculateDuty());

        return round($base * $this->vat_rate, 2);
    }

    public function totalTax(): float
    {
        return round($this->duty_amount + $this->vat_amount, 2);
    }
}
