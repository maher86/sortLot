<?php

namespace Tests\Feature\Packages;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PackageStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_package_status_when_transition_is_valid(): void
    {
        $user = $this->seededUser();
        $package = $this->packageFor($user, PackageStatus::InWarehouse);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/packages/{$package->id}/status", ['status' => PackageStatus::Sorting->value])
            ->assertOk()
            ->assertJsonPath('data.status', PackageStatus::Sorting->value)
            ->assertJsonPath('data.sorted_by', $user->id);

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'status' => PackageStatus::Sorting->value,
            'sorted_by' => $user->id,
        ]);
    }

    public function test_invalid_backward_status_transition_returns_422(): void
    {
        $user = $this->seededUser();
        $package = $this->packageFor($user, PackageStatus::Sorted);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/packages/{$package->id}/status", ['status' => PackageStatus::InWarehouse->value])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    private function seededUser(): User
    {
        $this->seed();

        return User::where('email', 'admin@sortlot.local')->firstOrFail();
    }

    private function packageFor(User $user, PackageStatus $status): Package
    {
        return Package::query()->create([
            'reference' => '2026-STATUS-'.$status->value,
            'origin_country' => 'US',
            'status' => $status,
            'created_by' => $user->id,
        ]);
    }
}
