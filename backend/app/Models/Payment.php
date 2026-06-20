<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'payment_no', 'order_id', 'created_by', 'type', 'method',
    'amount', 'currency', 'payment_date', 'transaction_no', 'remark',
    'fee_amount', 'status',
])]
class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isPlatform()) {
            return $query;
        }

        return $query->whereHas('order', function (Builder $q) use ($user) {
            if ($user->isSupplier()) {
                $q->where('supplier_id', $user->supplier_id);
            } elseif ($user->isDistributor() && $user->distributor_id) {
                $ids = [$user->distributor_id];

                if ($user->isRegionalAgent() && $user->distributor) {
                    $ids = array_merge($ids, $user->distributor->descendantIds());
                }

                $q->whereIn('distributor_id', $ids);
            } else {
                $q->whereRaw('1=0');
            }
        });
    }

    public function scopeOfOrder(Builder $query, $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByType(Builder $query, $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByMethod(Builder $query, $method): Builder
    {
        return $query->where('method', $method);
    }

    public function isEscrowDeposit(): bool
    {
        return $this->type === 'escrow_deposit';
    }

    public function isEscrowRelease(): bool
    {
        return $this->type === 'escrow_release';
    }

    public function isPlatformFee(): bool
    {
        return $this->type === 'platform_fee';
    }

    public function isRefund(): bool
    {
        return $this->type === 'refund';
    }

    public function isIncome(): bool
    {
        return in_array($this->type, ['escrow_deposit', 'platform_fee'], true);
    }

    public function isExpense(): bool
    {
        return in_array($this->type, ['escrow_release', 'refund'], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
