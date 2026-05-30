<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_and_permissions_seeded(): void
    {
        $this->seed();

        $this->assertDatabaseHas('roles', ['name' => 'super_admin']);
        $this->assertDatabaseHas('permissions', ['name' => 'packages.create']);

        $admin = User::where('email', 'admin@sortlot.local')->firstOrFail();

        $this->assertTrue($admin->hasRole('super_admin'));
    }
}
