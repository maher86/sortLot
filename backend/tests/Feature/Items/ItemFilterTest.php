<?php

namespace Tests\Feature\Items;

use App\Enums\ItemGender;
use App\Enums\ItemSeason;
use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Package;
use App\Models\PricingTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ItemFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_items_can_be_filtered_by_combined_fields(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@sortlot.local')->firstOrFail();
        Sanctum::actingAs($user);
        $shirt = ItemType::where('slug', 'shirt')->firstOrFail();
        $pants = ItemType::where('slug', 'pants')->firstOrFail();
        $k1 = PricingTier::where('code', 'K1')->firstOrFail();
        $k2 = PricingTier::where('code', 'K2')->firstOrFail();
        $package = $this->packageFor($user);

        $matching = $this->itemFor($package, $shirt, $k1, [
            'sku' => 'FILTER-MATCH',
            'barcode' => 'FILTER-001',
            'season' => ItemSeason::Winter,
            'gender' => ItemGender::Man,
            'status' => ItemStatus::Reserved,
        ]);

        $this->itemFor($package, $pants, $k2, [
            'sku' => 'FILTER-MISS',
            'barcode' => 'FILTER-002',
            'season' => ItemSeason::Summer,
            'gender' => ItemGender::Woman,
            'status' => ItemStatus::Available,
        ]);

        $this->getJson('/api/v1/items?filter[season]=winter&filter[gender]=man&filter[item_type_id]='.$shirt->id.'&filter[pricing_tier_id]='.$k1->id.'&filter[package_id]='.$package->id.'&filter[status]=reserved&search=FILTER')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id);
    }

    private function packageFor(User $user): Package
    {
        return Package::query()->create([
            'reference' => '2026-ITEM-FILTER',
            'origin_country' => 'US',
            'created_by' => $user->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function itemFor(Package $package, ItemType $itemType, PricingTier $pricingTier, array $overrides): Item
    {
        return Item::query()->create([
            'package_id' => $package->id,
            'season' => ItemSeason::General,
            'gender' => ItemGender::Boy,
            'item_type_id' => $itemType->id,
            'pricing_tier_id' => $pricingTier->id,
            'status' => ItemStatus::Available,
            'unit_price_fils' => 1000,
            ...$overrides,
        ]);
    }
}
