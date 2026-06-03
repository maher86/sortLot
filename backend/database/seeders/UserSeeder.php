<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (! Role::query()->where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
            $this->call([
                PermissionSeeder::class,
                RoleSeeder::class,
            ]);
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@sortlot.local'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'is_active' => true,
            ],
        );

        $admin->assignRole('super_admin');
    }
}
