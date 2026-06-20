<?php

namespace App\Models;

use App\Enums\CustomsDeclarationStatus;
use App\Models\Concerns\HasVisibilityScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'declaration_no', 'shipment_id', 'order_id', 'type', 'status',
    'declarant', 'declaration_date', 'release_date', 'hs_code_summary',
    'declared_value', 'currency', 'tax_amount', 'duty_amount',
    'vat_amount', 'total_fee', 'customs_broker', 'documents', 'remark',
])]
class CustomsDeclaration extends Model
{
    use HasFactory, SoftDeletes, HasVisibilityScope;

    protected array $visibilityMap = [
        'supplier' => ['relation' => 'order', 'foreign_key' => 'supplier_id'],
        'distributor' => ['relation' => 'order', 'foreign_key' => 'distributor_id', 'include_descendants' => true],
    ];

    protected function casts(): array
    {
        return [
            'declaration_date' => 'date',
            'release_date' => 'date',
            'declared_value' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'duty_amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total_fee' => 'decimal:2',
            'documents' => 'array',
            'status' => CustomsDeclarationStatus::class,
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomsDeclarationItem::class);
    }

    public function scopeByStatus(Builder $query, CustomsDeclarationStatus|string $status): Builder
    {
        $value = $status instanceof CustomsDeclarationStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByOrder(Builder $query, $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }

    public function isImport(): bool
    {
        return $this->type === 'import';
    }

    public function isExport(): bool
    {
        return $this->type === 'export';
    }

    public function isPending(): bool
    {
        return $this->status === CustomsDeclarationStatus::PENDING;
    }

    public function isDeclared(): bool
    {
        return $this->status === CustomsDeclarationStatus::DECLARED;
    }

    public function isInspecting(): bool
    {
        return $this->status === CustomsDeclarationStatus::INSPECTING;
    }

    public function isReleased(): bool
    {
        return $this->status === CustomsDeclarationStatus::RELEASED;
    }

    public function isRejected(): bool
    {
        return $this->status === CustomsDeclarationStatus::REJECTED;
    }

    public function getStatusEnum(): CustomsDeclarationStatus
    {
        return CustomsDeclarationStatus::from($this->getRawOriginal('status') ?? $this->status);
    }
}
