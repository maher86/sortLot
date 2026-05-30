<?php

namespace Tests\Unit\Models;

use App\Enums\ItemGender;
use App\Enums\ItemSeason;
use App\Enums\ItemStatus;
use App\Enums\PackageStatus;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Package;
use App\Models\PricingTier;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_casts_status_and_exposes_relationships(): void
    {
        $user = User::factory()->create();
        $package = Package::query()->create([
            'reference' => '2026-00001',
            'origin_country' => 'US',
            'status' => PackageStatus::Sorting,
            'created_by' => $user->id,
            'sorted_by' => $user->id,
        ]);

        $this->assertSame(PackageStatus::Sorting, $package->status);
        $this->assertInstanceOf(BelongsTo::class, $package->supplier());
        $this->assertInstanceOf(BelongsTo::class, $package->sortedBy());
        $this->assertInstanceOf(HasMany::class, $package->items());
        $this->assertInstanceOf(BelongsTo::class, $package->purchaseOrder());
    }

    public function test_package_scopes_and_item_count_accessors(): void
    {
        $user = User::factory()->create();
        $pricingTier = PricingTier::query()->create(['code' => 'K1', 'label' => 'Grade 1']);
        $itemType = ItemType::query()->create(['name' => 'Shirt', 'slug' => 'shirt']);

        $sorted = Package::query()->create([
            'reference' => '2026-00002',
            'origin_country' => 'US',
            'status' => PackageStatus::Sorted,
            'created_by' => $user->id,
        ]);
        $unsorted = Package::query()->create([
            'reference' => '2026-00003',
            'origin_country' => 'US',
            'status' => PackageStatus::InWarehouse,
            'created_by' => $user->id,
        ]);

        Item::query()->create([
            'package_id' => $sorted->id,
            'season' => ItemSeason::Summer,
            'gender' => ItemGender::Man,
            'item_type_id' => $itemType->id,
            'pricing_tier_id' => $pricingTier->id,
            'status' => ItemStatus::Available,
            'unit_price_fils' => 1200,
        ]);
        Item::query()->create([
            'package_id' => $sorted->id,
            'season' => ItemSeason::Winter,
            'gender' => ItemGender::Woman,
            'item_type_id' => $itemType->id,
            'pricing_tier_id' => $pricingTier->id,
            'status' => ItemStatus::Sold,
            'unit_price_fils' => 1500,
        ]);

        $this->assertTrue(PackageStatus::InWarehouse->canTransitionTo(PackageStatus::Sorting));
        $this->assertTrue(PackageStatus::Sorted->canTransitionTo(PackageStatus::Sorting));
        $this->assertFalse(PackageStatus::Sorted->canTransitionTo(PackageStatus::InWarehouse));
        $this->assertTrue(Package::query()->byStatus(PackageStatus::Sorted)->whereKey($sorted->id)->exists());
        $this->assertTrue(Package::query()->sorted()->whereKey($sorted->id)->exists());
        $this->assertTrue(Package::query()->unsorted()->whereKey($unsorted->id)->exists());
        $this->assertSame(2, $sorted->items_count);
        $this->assertSame(1, $sorted->available_items_count);
    }
}
