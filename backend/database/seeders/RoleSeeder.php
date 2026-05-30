<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public const ROLE_PERMISSIONS = [
        'super_admin' => PermissionSeeder::PERMISSIONS,
        'manager' => [
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
            'preferences.view',
            'preferences.edit',
            'pricing_tiers.manage',
            'item_types.manage',
            'dashboard.view',
            'reports.financial',
            'reports.inventory',
            'reports.export',
            'audit_logs.view',
        ],
        'warehouse_staff' => [
            'packages.view',
            'packages.create',
            'packages.edit',
            'packages.change_status',
            'items.view',
            'items.create',
            'items.edit',
            'items.change_status',
            'dashboard.view',
            'reports.inventory',
        ],
        'accountant' => [
            'packages.view',
            'items.view',
            'customers.view',
            'customers.create',
            'customers.edit',
            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'sales_orders.view',
            'sales_orders.create',
            'sales_orders.edit',
            'sales_orders.confirm',
            'purchase_orders.view',
            'purchase_orders.create',
            'purchase_orders.edit',
            'purchase_orders.confirm',
            'payments.view',
            'payments.create',
            'preferences.view',
            'dashboard.view',
            'reports.financial',
            'reports.inventory',
            'reports.export',
        ],
        'viewer' => [
            'packages.view',
            'items.view',
            'customers.view',
            'suppliers.view',
            'sales_orders.view',
            'purchase_orders.view',
            'payments.view',
            'dashboard.view',
            'reports.inventory',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            Role::findOrCreate($roleName, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
