<?php

namespace Tests\Feature\Packages;

use App\Enums\ItemGender;
use App\Enums\ItemSeason;
use App\Enums\ItemStatus;
use App\Enums\PackageStatus;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Package;
use App\Models\PricingTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PackageMutationRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_can_be_updated_and_deleted_before_sorting_starts(): void
    {
        $user = $this->seededUser();
        Sanctum::actingAs($user);

        $package = Package::query()->create([
            'reference' => 'MUTABLE-PKG',
            'origin_country' => 'US',
            'status' => PackageStatus::InWarehouse,
            'created_by' => $user->id,
        ]);

        $this->patchJson("/api/v1/packages/{$package->id}", ['notes' => 'Updated before sorting'])
            ->assertOk()
            ->assertJsonPath('data.notes', 'Updated before sorting');

        $this->deleteJson("/api/v1/packages/{$package->id}")
            ->assertNoContent();
    }

    public function test_package_cannot_be_updated_or_deleted_after_sorting_starts(): void
    {
        $user = $this->seededUser();
        Sanctum::actingAs($user);

        $package = Package::query()->create([
            'reference' => 'SORTING-PKG',
            'origin_country' => 'US',
            'status' => PackageStatus::Sorting,
            'created_by' => $user->id,
        ]);

        $this->patchJson("/api/v1/packages/{$package->id}", ['notes' => 'Too late'])
            ->assertStatus(409);

        $this->deleteJson("/api/v1/packages/{$package->id}")
            ->assertStatus(409);
    }

    public function test_package_with_items_cannot_be_deleted(): void
    {
        $user = $this->seededUser();
        Sanctum::actingAs($user);

        $package = Package::query()->create([
            'reference' => 'HAS-ITEMS-PKG',
            'origin_country' => 'US',
            'status' => PackageStatus::InWarehouse,
            'created_by' => $user->id,
        ]);

        Item::query()->create([
            'package_id' => $package->id,
            'season' => ItemSeason::Summer,
            'gender' => ItemGender::Woman,
            'item_type_id' => ItemType::query()->firstOrFail()->id,
            'pricing_tier_id' => PricingTier::query()->firstOrFail()->id,
            'condition_notes' => 'Blocks delete',
            'status' => ItemStatus::Available,
            'unit_price_fils' => 1000,
        ]);

        $this->deleteJson("/api/v1/packages/{$package->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'PACKAGE_DELETE_NOT_ALLOWED');
    }

    private function seededUser(): User
    {
        $this->seed();

        return User::where('email', 'admin@sortlot.local')->firstOrFail();
    }
}
