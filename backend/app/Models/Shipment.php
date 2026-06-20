<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use App\Models\Concerns\HasVisibilityScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'tracking_no', 'order_id', 'shipping_method_id', 'carrier',
    'origin_warehouse_id', 'destination_warehouse_id',
    'origin_market_id', 'destination_market_id', 'sender_name',
    'sender_address', 'receiver_name', 'receiver_phone',
    'receiver_address', 'receiver_email', 'receiver_city',
    'receiver_state', 'receiver_postal_code', 'receiver_country',
    'weight', 'volume', 'packages', 'declared_value', 'currency',
    'shipping_cost', 'insurance_cost', 'fuel_surcharge', 'other_fee',
    'total_cost', 'status', 'shipped_at', 'in_transit_at',
    'customs_at', 'delivered_at', 'failed_at', 'tracking_history', 'remark',
])]
class Shipment extends Model
{
    use HasFactory, SoftDeletes, HasVisibilityScope;

    protected array $visibilityMap = [
        'supplier' => ['relation' => 'order', 'foreign_key' => 'supplier_id'],
        'distributor' => ['relation' => 'order', 'foreign_key' => 'distributor_id', 'include_descendants' => true],
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:3',
            'volume' => 'decimal:3',
            'packages' => 'integer',
            'declared_value' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'insurance_cost' => 'decimal:2',
            'fuel_surcharge' => 'decimal:2',
            'other_fee' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'shipped_at' => 'datetime',
            'in_transit_at' => 'datetime',
            'customs_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'tracking_history' => 'array',
            'status' => ShipmentStatus::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function originWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function originMarket(): BelongsTo
    {
        return $this->belongsTo(Market::class, 'origin_market_id');
    }

    public function destinationMarket(): BelongsTo
    {
        return $this->belongsTo(Market::class, 'destination_market_id');
    }

    public function declarations(): HasMany
    {
        return $this->hasMany(CustomsDeclaration::class);
    }

    public function scopeByStatus(Builder $query, ShipmentStatus|string $status): Builder
    {
        $value = $status instanceof ShipmentStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    public function scopeByOrder(Builder $query, $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByDestination(Builder $query, $marketId): Builder
    {
        return $query->where('destination_market_id', $marketId);
    }

    public function scopeShippedBetween(Builder $query, $start, $end): Builder
    {
        return $query->whereBetween('shipped_at', [$start, $end]);
    }

    public function isPending(): bool
    {
        return $this->status === ShipmentStatus::PENDING;
    }

    public function isShipped(): bool
    {
        return $this->status === ShipmentStatus::SHIPPED;
    }

    public function isInTransit(): bool
    {
        return $this->status === ShipmentStatus::IN_TRANSIT;
    }

    public function isCustoms(): bool
    {
        return $this->status === ShipmentStatus::CUSTOMS;
    }

    public function isDelivered(): bool
    {
        return $this->status === ShipmentStatus::DELIVERED;
    }

    public function isFailed(): bool
    {
        return $this->status === ShipmentStatus::FAILED;
    }

    public function isReturned(): bool
    {
        return $this->status === ShipmentStatus::RETURNED;
    }

    public function isCancelled(): bool
    {
        return $this->status === ShipmentStatus::CANCELLED;
    }

    public function addTrackingEvent(string $status, string $location, string $description): void
    {
        $history = $this->tracking_history ?? [];
        $history[] = [
            'status' => $status,
            'location' => $location,
            'description' => $description,
            'time' => now()->toDateTimeString(),
        ];
        $this->tracking_history = $history;
    }

    public function getStatusEnum(): ShipmentStatus
    {
        return ShipmentStatus::from($this->getRawOriginal('status') ?? $this->status);
    }
}
