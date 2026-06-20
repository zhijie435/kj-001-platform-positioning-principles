<?php

namespace App\Models;

use App\Models\Concerns\HasVisibilityScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'company_name', 'business_license', 'contact_person',
    'phone', 'email', 'address', 'bank_name', 'bank_account',
    'credit_limit', 'balance', 'status', 'remark',
    'country_code', 'tax_id', 'export_license', 'import_export_code',
    'certifications', 'serviced_markets', 'is_cross_border',
])]
class Supplier extends Model
{
    use HasFactory, SoftDeletes, HasVisibilityScope;

    protected array $visibilityMap = [
        'supplier' => ['foreign_key' => 'id'],
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'balance' => 'decimal:2',
            'certifications' => 'array',
            'serviced_markets' => 'array',
            'is_cross_border' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function shipments(): HasManyThrough
    {
        return $this->hasManyThrough(Shipment::class, Order::class);
    }

    public function scopeCrossBorder(Builder $query): Builder
    {
        return $query->where('is_cross_border', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
