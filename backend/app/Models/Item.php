<?php

namespace App\Models;

use App\Enums\ItemGender;
use App\Enums\ItemSeason;
use App\Enums\ItemStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use Auditable, HasUlids, SoftDeletes;

    protected $fillable = [
        'package_id',
        'sku',
        'barcode',
        'season',
        'gender',
        'item_type_id',
        'pricing_tier_id',
        'condition_notes',
        'status',
        'quantity',
        'weight_kg',
        'unit_price_fils',
        'sales_order_id',
        'sorted_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (Item $item): void {
            if ($item->sku) {
                return;
            }

            $package = $item->package()->first();
            $sequence = static::query()
                ->where('package_id', $item->package_id)
                ->withTrashed()
                ->count() + 1;

            $item->sku = sprintf('PKG-%s-%05d', $package?->reference ?? $item->package_id, $sequence);
        });
    }

    protected function casts(): array
    {
        return [
            'season' => ItemSeason::class,
            'gender' => ItemGender::class,
            'status' => ItemStatus::class,
            'quantity' => 'integer',
            'weight_kg' => 'decimal:2',
            'unit_price_fils' => 'integer',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function itemType(): BelongsTo
    {
        return $this->belongsTo(ItemType::class);
    }

    public function pricingTier(): BelongsTo
    {
        return $this->belongsTo(PricingTier::class);
    }

    public function sortedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sorted_by');
    }
}
