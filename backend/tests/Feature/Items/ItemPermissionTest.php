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

class ItemPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_read_items_but_cannot_create_them(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@sortlot.local')->firstOrFail();
        $viewer = User::factory()->create(['email' => 'items-viewer@example.test']);
        $viewer->assignRole('viewer');
        Sanctum::actingAs($viewer);
        $item = $this->itemFor($admin);

        $this->getJson('/api/v1/items')
            ->assertOk()
            ->assertJsonPath('data.0.id', $item->id);

        $this->postJson('/api/v1/items', $this->payloadFor($item->package_id))
            ->assertForbidden();
    }

    public function test_warehouse_staff_can_create_and_change_item_status(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@sortlot.local')->firstOrFail();
        $warehouse = User::factory()->create(['email' => 'items-warehouse@example.test']);
        $warehouse->assignRole('warehouse_staff');
        Sanctum::actingAs($warehouse);
        $package = $this->packageFor($admin, '2026-ITEM-WH');

        $create = $this->postJson('/api/v1/items', $this->payloadFor($package->id))
            ->assertCreated();

        $this->patchJson('/api/v1/items/'.$create->json('data.id').'/status', [
            'status' => ItemStatus::Missing->value,
            'reason' => 'Could not find during audit',
        ])
            ->assertOk();
    }

    public function test_accountant_can_read_but_cannot_change_item_status(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@sortlot.local')->firstOrFail();
        $accountant = User::factory()->create(['email' => 'items-accountant@example.test']);
        $accountant->assignRole('accountant');
        Sanctum::actingAs($accountant);
        $item = $this->itemFor($admin);

        $this->getJson('/api/v1/items/'.$item->id)
            ->assertOk();

        $this->patchJson('/api/v1/items/'.$item->id.'/status', [
            'status' => ItemStatus::Missing->value,
            'reason' => 'Inventory check',
        ])
            ->assertForbidden();
    }

    private function itemFor(User $user): Item
    {
        $package = $this->packageFor($user, '2026-ITEM-PERM');
        $payload = $this->payloadFor($package->id);

        return Item::query()->create($payload);
    }

    private function packageFor(User $user, string $reference): Package
    {
        return Package::query()->create([
            'reference' => $reference,
            'origin_country' => 'US',
            'created_by' => $user->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFor(string $packageId): array
    {
        return [
            'package_id' => $packageId,
            'season' => ItemSeason::General->value,
            'gender' => ItemGender::Boy->value,
            'item_type_id' => ItemType::query()->firstOrFail()->id,
            'pricing_tier_id' => PricingTier::query()->firstOrFail()->id,
            'unit_price_fils' => 1000,
        ];
    }
}
