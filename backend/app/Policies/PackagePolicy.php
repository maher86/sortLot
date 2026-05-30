<?php

namespace App\Policies;

use App\Models\Package;
use App\Models\User;

class PackagePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super_admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'warehouse_staff', 'accountant', 'viewer'])
            || $user->can('packages.view');
    }

    public function view(User $user, Package $package): bool
    {
        return $user->hasAnyRole(['manager', 'warehouse_staff', 'accountant', 'viewer'])
            || $user->can('packages.view');
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'warehouse_staff'])
            || $user->can('packages.create');
    }

    public function update(User $user, Package $package): bool
    {
        return $user->hasAnyRole(['manager', 'warehouse_staff'])
            || $user->can('packages.edit');
    }

    public function delete(User $user, Package $package): bool
    {
        return $user->hasRole('manager')
            || $user->can('packages.delete');
    }

    public function changeStatus(User $user, Package $package): bool
    {
        return $user->hasAnyRole(['manager', 'warehouse_staff'])
            || $user->can('packages.change_status');
    }

    public function createItems(User $user, Package $package): bool
    {
        return $user->hasAnyRole(['manager', 'warehouse_staff'])
            || $user->can('items.create');
    }
}
