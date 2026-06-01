<?php

namespace Tests\Feature\Packages;

use App\Enums\PackageStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PackageCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_list_show_update_and_delete_package(): void
    {
        $user = $this->seededUser();
        Sanctum::actingAs($user);

        $create = $this->postJson('/api/v1/packages', [
            'reference' => '2026-API-001',
            'origin_country' => 'US',
            'destination_country' => 'AE',
            'weight_kg' => 125.50,
            'number_of_bags' => 12,
            'notes' => 'First API package',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.reference', '2026-API-001')
            ->assertJsonPath('data.status', PackageStatus::InTransit->value)
            ->assertJsonPath('data.created_by', $user->id);

        $packageId = $create->json('data.id');

        $this->getJson('/api/v1/packages?filter[status]=in_transit&search=API-001')
            ->assertOk()
            ->assertJsonPath('data.0.id', $packageId);

        $this->getJson("/api/v1/packages/{$packageId}")
            ->assertOk()
            ->assertJsonPath('data.reference', '2026-API-001');

        $this->patchJson("/api/v1/packages/{$packageId}", ['notes' => 'Updated'])
            ->assertOk()
            ->assertJsonPath('data.notes', 'Updated');

        $this->deleteJson("/api/v1/packages/{$packageId}")
            ->assertNoContent();

        $this->assertSoftDeleted('packages', ['id' => $packageId]);
    }

    public function test_package_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/packages')->assertUnauthorized();
    }

    private function seededUser(string $email = 'admin@sortlot.local'): User
    {
        $this->seed();

        return User::where('email', $email)->firstOrFail();
    }
}
