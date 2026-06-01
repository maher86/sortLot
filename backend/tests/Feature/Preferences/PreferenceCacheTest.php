<?php

namespace Tests\Feature\Preferences;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PreferenceCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_preferences_are_returned_flat_and_cache_invalidates_on_update(): void
    {
        $this->seed();
        $manager = User::factory()->create(['email' => 'preferences-manager@example.test']);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->getJson('/api/v1/preferences')
            ->assertOk()
            ->assertJsonPath('data.company_name', 'SortLot Trading');

        Cache::tags('preferences')->put('preferences.flat', ['company_name' => 'Cached Company'], 300);

        $this->getJson('/api/v1/preferences')
            ->assertOk()
            ->assertJsonPath('data.company_name', 'Cached Company');

        $this->patchJson('/api/v1/preferences', [
            'company_name' => 'Updated SortLot',
            'payment_terms_days' => '45',
        ])
            ->assertOk()
            ->assertJsonPath('data.company_name', 'Updated SortLot')
            ->assertJsonPath('data.payment_terms_days', '45');

        $this->getJson('/api/v1/preferences')
            ->assertOk()
            ->assertJsonPath('data.company_name', 'Updated SortLot');
    }

    public function test_viewer_can_read_but_cannot_update_preferences(): void
    {
        $this->seed();
        $viewer = User::factory()->create(['email' => 'preferences-viewer@example.test']);
        $viewer->assignRole('viewer');
        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/preferences')->assertOk();

        $this->patchJson('/api/v1/preferences', ['company_name' => 'Nope'])
            ->assertForbidden();
    }
}
