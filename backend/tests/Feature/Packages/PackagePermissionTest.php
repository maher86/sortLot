<?php

namespace Tests\Feature\Packages;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PackagePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_read_packages_but_cannot_create_them(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@sortlot.local')->firstOrFail();
        $viewer = User::factory()->create(['email' => 'viewer@example.test']);
        $viewer->assignRole('viewer');
        Sanctum::actingAs($viewer);

        Package::query()->create([
            'reference' => '2026-VIEW-001',
            'origin_country' => 'US',
            'created_by' => $admin->id,
        ]);

        $this->getJson('/api/v1/packages')
            ->assertOk()
            ->assertJsonPath('data.0.reference', '2026-VIEW-001');

        $this->postJson('/api/v1/packages', [
            'reference' => '2026-VIEW-002',
            'origin_country' => 'US',
        ])
            ->assertForbidden();
    }

    public function test_warehouse_staff_can_create_and_change_package_status(): void
    {
        $this->seed();
        $warehouse = User::factory()->create(['email' => 'warehouse@example.test']);
        $warehouse->assignRole('warehouse_staff');
        Sanctum::actingAs($warehouse);

        $create = $this->postJson('/api/v1/packages', [
            'reference' => '2026-WH-001',
            'origin_country' => 'US',
            'status' => PackageStatus::InWarehouse->value,
        ])
            ->assertCreated();

        $this->patchJson('/api/v1/packages/'.$create->json('data.id').'/status', [
            'status' => PackageStatus::Sorting->value,
        ])
            ->assertOk();
    }

    public function test_accountant_can_read_but_cannot_change_package_status(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@sortlot.local')->firstOrFail();
        $accountant = User::factory()->create(['email' => 'accountant@example.test']);
        $accountant->assignRole('accountant');
        Sanctum::actingAs($accountant);
        $package = Package::query()->create([
            'reference' => '2026-ACC-001',
            'origin_country' => 'US',
            'status' => PackageStatus::InWarehouse,
            'created_by' => $admin->id,
        ]);

        $this->getJson('/api/v1/packages/'.$package->id)
            ->assertOk();

        $this->patchJson('/api/v1/packages/'.$package->id.'/status', [
            'status' => PackageStatus::Sorting->value,
        ])
            ->assertForbidden();
    }
}
