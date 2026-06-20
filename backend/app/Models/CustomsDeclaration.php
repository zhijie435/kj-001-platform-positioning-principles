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
    'declaration_no', 'shipment_id', 'order_id', 'type', 'status',
    'declarant', 'declaration_date', 'release_date', 'hs_code_summary',
    'declared_value', 'currency', 'tax_amount', 'duty_amount',
    'vat_amount', 'total_fee', 'customs_broker', 'documents', 'remark',
])]
class CustomsDeclaration extends Model
{
    use HasFactory, SoftDeletes;

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

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isPlatform()) {
            return $query;
        }

        if ($user->isSupplier()) {
            return $query->whereHas('order', function (Builder $q) use ($user) {
                $q->where('supplier_id', $user->supplier_id);
            });
        }

        if ($user->isDistributor() && $user->distributor_id) {
            return $query->whereHas('order', function (Builder $q) use ($user) {
                $ids = [$user->distributor_id];

                if ($user->isRegionalAgent() && $user->distributor) {
                    $ids = array_merge($ids, $user->distributor->descendantIds());
                }

                $q->whereIn('distributor_id', $ids);
            });
        }

        return $query->whereRaw('1=0');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
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
        return $this->status === 'pending';
    }

    public function isDeclared(): bool
    {
        return $this->status === 'declared';
    }

    public function isInspecting(): bool
    {
        return $this->status === 'inspecting';
    }

    public function isReleased(): bool
    {
        return $this->status === 'released';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
