<?php

namespace Tests\Feature\Items;

use App\Enums\ItemGender;
use App\Enums\ItemSeason;
use App\Enums\ItemStatus;
use App\Models\ItemType;
use App\Models\Package;
use App\Models\PricingTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ItemCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_list_show_update_lookup_and_delete_item(): void
    {
        $user = $this->seededUser();
        Sanctum::actingAs($user);
        $package = $this->packageFor($user);
        $itemType = ItemType::query()->firstOrFail();
        $pricingTier = PricingTier::query()->firstOrFail();

        $create = $this->postJson('/api/v1/items', [
            'package_id' => $package->id,
            'barcode' => 'BC-CRUD-001',
            'season' => ItemSeason::Summer->value,
            'gender' => ItemGender::Woman->value,
            'item_type_id' => $itemType->id,
            'pricing_tier_id' => $pricingTier->id,
            'condition_notes' => 'Good condition',
            'unit_price_fils' => 1500,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.package_id', $package->id)
            ->assertJsonPath('data.barcode', 'BC-CRUD-001')
            ->assertJsonPath('data.status', ItemStatus::Available->value)
            ->assertJsonPath('data.sorted_by', $user->id);

        $itemId = $create->json('data.id');
        $sku = $create->json('data.sku');

        $this->getJson('/api/v1/items?search=BC-CRUD-001')
            ->assertOk()
            ->assertJsonPath('data.0.id', $itemId);

        $this->getJson("/api/v1/items/{$itemId}")
            ->assertOk()
            ->assertJsonPath('data.sku', $sku);

        $this->getJson("/api/v1/items/sku/{$sku}")
            ->assertOk()
            ->assertJsonPath('data.id', $itemId);

        $this->getJson('/api/v1/items/barcode/BC-CRUD-001')
            ->assertOk()
            ->assertJsonPath('data.id', $itemId);

        $this->patchJson("/api/v1/items/{$itemId}", ['condition_notes' => 'Excellent'])
            ->assertOk()
            ->assertJsonPath('data.condition_notes', 'Excellent');

        $this->deleteJson("/api/v1/items/{$itemId}")
            ->assertNoContent();

        $this->assertSoftDeleted('items', ['id' => $itemId]);
    }

    public function test_item_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/items')->assertUnauthorized();
    }

    private function seededUser(): User
    {
        $this->seed();

        return User::where('email', 'admin@sortlot.local')->firstOrFail();
    }

    private function packageFor(User $user): Package
    {
        return Package::query()->create([
            'reference' => '2026-ITEM-CRUD',
            'origin_country' => 'US',
            'created_by' => $user->id,
        ]);
    }
}
