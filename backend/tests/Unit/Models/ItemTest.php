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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_generates_sku_from_package_reference_and_sequence(): void
    {
        $user = User::factory()->create();
        $package = Package::query()->create([
            'reference' => '2026-00004',
            'origin_country' => 'US',
            'status' => PackageStatus::Sorting,
            'created_by' => $user->id,
        ]);
        $pricingTier = PricingTier::query()->create(['code' => 'K1', 'label' => 'Grade 1']);
        $itemType = ItemType::query()->create(['name' => 'Pants', 'slug' => 'pants']);

        $first = $this->createItem($package, $itemType, $pricingTier);
        $second = $this->createItem($package, $itemType, $pricingTier);

        $this->assertSame('PKG-2026-00004-00001', $first->sku);
        $this->assertSame('PKG-2026-00004-00002', $second->sku);
    }

    public function test_item_casts_enums_and_exposes_relationships(): void
    {
        $user = User::factory()->create();
        $package = Package::query()->create([
            'reference' => '2026-00005',
            'origin_country' => 'US',
            'created_by' => $user->id,
        ]);
        $pricingTier = PricingTier::query()->create(['code' => 'K2', 'label' => 'Grade 2']);
        $itemType = ItemType::query()->create(['name' => 'Dress', 'slug' => 'dress']);

        $item = $this->createItem($package, $itemType, $pricingTier, [
            'season' => ItemSeason::Spring,
            'gender' => ItemGender::Girl,
            'status' => ItemStatus::Reserved,
            'sorted_by' => $user->id,
        ]);

        $this->assertSame(ItemSeason::Spring, $item->season);
        $this->assertSame(ItemGender::Girl, $item->gender);
        $this->assertSame(ItemStatus::Reserved, $item->status);
        $this->assertInstanceOf(BelongsTo::class, $item->package());
        $this->assertInstanceOf(BelongsTo::class, $item->itemType());
        $this->assertInstanceOf(BelongsTo::class, $item->pricingTier());
        $this->assertInstanceOf(BelongsTo::class, $item->sortedBy());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createItem(Package $package, ItemType $itemType, PricingTier $pricingTier, array $overrides = []): Item
    {
        return Item::query()->create([
            'package_id' => $package->id,
            'season' => ItemSeason::Summer,
            'gender' => ItemGender::Man,
            'item_type_id' => $itemType->id,
            'pricing_tier_id' => $pricingTier->id,
            'status' => ItemStatus::Available,
            'unit_price_fils' => 1200,
            ...$overrides,
        ]);
    }
}
