<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Concerns\HasVisibilityScope;
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
    use HasFactory, SoftDeletes, HasVisibilityScope;

    protected array $visibilityMap = [
        'supplier' => ['relation' => 'order', 'foreign_key' => 'supplier_id'],
        'distributor' => ['relation' => 'order', 'foreign_key' => 'distributor_id', 'include_descendants' => true],
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'payment_date' => 'date',
            'type' => PaymentType::class,
            'status' => PaymentStatus::class,
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

    public function scopeOfOrder(Builder $query, $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByType(Builder $query, PaymentType|string $type): Builder
    {
        $value = $type instanceof PaymentType ? $type->value : $type;

        return $query->where('type', $value);
    }

    public function scopeByMethod(Builder $query, $method): Builder
    {
        return $query->where('method', $method);
    }

    public function isEscrowDeposit(): bool
    {
        return $this->type === PaymentType::ESCROW_DEPOSIT;
    }

    public function isEscrowRelease(): bool
    {
        return $this->type === PaymentType::ESCROW_RELEASE;
    }

    public function isPlatformFee(): bool
    {
        return $this->type === PaymentType::PLATFORM_FEE;
    }

    public function isRefund(): bool
    {
        return $this->type === PaymentType::REFUND;
    }

    public function isIncome(): bool
    {
        $type = $this->getTypeEnum();

        return $type?->isIncome() ?? false;
    }

    public function isExpense(): bool
    {
        $type = $this->getTypeEnum();

        return $type?->isExpense() ?? false;
    }

    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    public function getTypeEnum(): ?PaymentType
    {
        $raw = $this->getRawOriginal('type') ?? $this->type;

        return $raw ? PaymentType::tryFrom($raw) : null;
    }

    public function getStatusEnum(): ?PaymentStatus
    {
        $raw = $this->getRawOriginal('status') ?? $this->status;

        return $raw ? PaymentStatus::tryFrom($raw) : null;
    }
}
