<?php

namespace App\Models;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Models\Concerns\HasVisibilityScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'order_no', 'type', 'supplier_id', 'distributor_id', 'created_by',
    'subtotal', 'tax', 'discount', 'shipping', 'total', 'paid_amount',
    'payment_status', 'status', 'shipping_address', 'billing_address',
    'tracking_no', 'confirmed_at', 'shipped_at', 'delivered_at',
    'completed_at', 'remark',
    'market_id', 'currency', 'exchange_rate', 'is_cross_border',
    'incoterms', 'insurance_fee', 'duty_fee', 'vat_fee',
    'customs_fee', 'other_fee',
])]
class Order extends Model
{
    use HasFactory, SoftDeletes, HasVisibilityScope;

    protected array $visibilityMap = [
        'supplier' => ['foreign_key' => 'supplier_id'],
        'distributor' => ['foreign_key' => 'distributor_id', 'include_descendants' => true],
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'shipping' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
            'exchange_rate' => 'decimal:6',
            'is_cross_border' => 'boolean',
            'insurance_fee' => 'decimal:2',
            'duty_fee' => 'decimal:2',
            'vat_fee' => 'decimal:2',
            'customs_fee' => 'decimal:2',
            'other_fee' => 'decimal:2',
            'status' => OrderStatus::class,
            'payment_status' => OrderPaymentStatus::class,
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function declarations(): HasMany
    {
        return $this->hasMany(CustomsDeclaration::class);
    }

    public function scopeCrossBorder(Builder $query): Builder
    {
        return $query->where('is_cross_border', true);
    }

    public function scopeByMarket(Builder $query, $marketId): Builder
    {
        return $query->where('market_id', $marketId);
    }

    public function calculateCrossBorderTotal(): float
    {
        return round(
            $this->subtotal + $this->tax + $this->shipping + $this->insurance_fee
            + $this->duty_fee + $this->vat_fee + $this->customs_fee
            + $this->other_fee - $this->discount,
            2
        );
    }

    public function scopeOfSupplier(Builder $query, $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeOfDistributor(Builder $query, $distributorId): Builder
    {
        return $query->where('distributor_id', $distributorId);
    }

    public function scopeByStatus(Builder $query, OrderStatus|string $status): Builder
    {
        $value = $status instanceof OrderStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    public function scopeByPaymentStatus(Builder $query, OrderPaymentStatus|string $status): Builder
    {
        $value = $status instanceof OrderPaymentStatus ? $status->value : $status;

        return $query->where('payment_status', $value);
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === OrderStatus::CONFIRMED;
    }

    public function isShipped(): bool
    {
        return $this->status === OrderStatus::SHIPPED;
    }

    public function isDelivered(): bool
    {
        return $this->status === OrderStatus::DELIVERED;
    }

    public function isCompleted(): bool
    {
        return $this->status === OrderStatus::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::CANCELLED;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === OrderPaymentStatus::PAID;
    }

    public function isUnpaid(): bool
    {
        return $this->payment_status === OrderPaymentStatus::UNPAID;
    }

    public function isPartialPaid(): bool
    {
        return $this->payment_status === OrderPaymentStatus::PARTIAL;
    }

    public function getStatusEnum(): OrderStatus
    {
        return OrderStatus::from($this->getRawOriginal('status') ?? $this->status);
    }

    public function getPaymentStatusEnum(): OrderPaymentStatus
    {
        return OrderPaymentStatus::from($this->getRawOriginal('payment_status') ?? $this->payment_status);
    }
}
