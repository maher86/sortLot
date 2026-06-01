<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'users.view',
        'users.create',
        'users.edit',
        'users.delete',
        'roles.manage',
        'packages.view',
        'packages.create',
        'packages.edit',
        'packages.delete',
        'packages.change_status',
        'items.view',
        'items.create',
        'items.edit',
        'items.delete',
        'items.change_status',
        'customers.view',
        'customers.create',
        'customers.edit',
        'customers.delete',
        'suppliers.view',
        'suppliers.create',
        'suppliers.edit',
        'suppliers.delete',
        'sales_orders.view',
        'sales_orders.create',
        'sales_orders.edit',
        'sales_orders.delete',
        'sales_orders.confirm',
        'sales_orders.cancel',
        'purchase_orders.view',
        'purchase_orders.create',
        'purchase_orders.edit',
        'purchase_orders.delete',
        'purchase_orders.confirm',
        'payments.view',
        'payments.create',
        'payments.delete',
        'preferences.view',
        'preferences.edit',
        'pricing_tiers.manage',
        'item_types.manage',
        'dashboard.view',
        'reports.financial',
        'reports.inventory',
        'reports.export',
        'audit_logs.view',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
