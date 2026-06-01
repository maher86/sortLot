<?php

namespace App\Models;

use App\Enums\ItemStatus;
use App\Enums\PackageStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use Auditable, HasUlids, SoftDeletes;

    protected $fillable = [
        'reference',
        'supplier_id',
        'purchase_order_id',
        'origin_country',
        'destination_country',
        'status',
        'weight_kg',
        'number_of_bags',
        'notes',
        'arrived_at',
        'sorting_started_at',
        'sorting_completed_at',
        'sorted_by',
        'created_by',
    ];

    protected $appends = [
        'items_count',
        'available_items_count',
    ];

    protected function casts(): array
    {
        return [
            'status' => PackageStatus::class,
            'weight_kg' => 'decimal:2',
            'number_of_bags' => 'integer',
            'arrived_at' => 'datetime',
            'sorting_started_at' => 'datetime',
            'sorting_completed_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function sortedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sorted_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'purchase_order_id');
    }

    public function getItemsCountAttribute(): int
    {
        return (int) ($this->attributes['items_count'] ?? $this->items()->count());
    }

    public function getAvailableItemsCountAttribute(): int
    {
        return (int) ($this->attributes['available_items_count'] ?? $this->items()
            ->where('status', ItemStatus::Available->value)
            ->count());
    }

    public function scopeByStatus(Builder $query, PackageStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof PackageStatus ? $status->value : $status);
    }

    public function scopeUnsorted(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PackageStatus::InTransit->value,
            PackageStatus::AtPort->value,
            PackageStatus::InCustoms->value,
            PackageStatus::InWarehouse->value,
            PackageStatus::Sorting->value,
        ]);
    }

    public function scopeSorted(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PackageStatus::Sorted->value,
            PackageStatus::PartiallyShipped->value,
            PackageStatus::Shipped->value,
            PackageStatus::Closed->value,
        ]);
    }
}
