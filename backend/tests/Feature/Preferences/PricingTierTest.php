<?php

namespace Tests\Feature\Preferences;

use App\Models\PricingTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PricingTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_crud_pricing_tiers(): void
    {
        $this->seed();
        $manager = User::factory()->create(['email' => 'pricing-manager@example.test']);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $create = $this->postJson('/api/v1/preferences/pricing-tiers', [
            'code' => 'QA',
            'label' => 'Quality A',
            'price_per_kg_fils' => 1350,
            'sort_order' => 20,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.code', 'QA')
            ->assertJsonPath('data.price_per_kg_fils', 1350);

        $id = $create->json('data.id');

        $this->getJson('/api/v1/preferences/pricing-tiers')
            ->assertOk()
            ->assertJsonFragment(['code' => 'QA']);

        $this->patchJson("/api/v1/preferences/pricing-tiers/{$id}", [
            'label' => 'Quality A Updated',
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.label', 'Quality A Updated')
            ->assertJsonPath('data.is_active', false);

        $this->deleteJson("/api/v1/preferences/pricing-tiers/{$id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('pricing_tiers', ['id' => $id]);
    }

    public function test_viewer_can_list_but_cannot_create_pricing_tiers(): void
    {
        $this->seed();
        $viewer = User::factory()->create(['email' => 'pricing-viewer@example.test']);
        $viewer->assignRole('viewer');
        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/preferences/pricing-tiers')
            ->assertOk()
            ->assertJsonFragment(['code' => PricingTier::query()->firstOrFail()->code]);

        $this->postJson('/api/v1/preferences/pricing-tiers', [
            'code' => 'NO',
            'label' => 'Not Allowed',
        ])
            ->assertForbidden();
    }
}
