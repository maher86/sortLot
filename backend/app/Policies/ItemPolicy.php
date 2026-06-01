<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super_admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'warehouse_staff', 'accountant', 'viewer'])
            || $user->can('items.view');
    }

    public function view(User $user, Item $item): bool
    {
        return $user->hasAnyRole(['manager', 'warehouse_staff', 'accountant', 'viewer'])
            || $user->can('items.view');
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'warehouse_staff'])
            || $user->can('items.create');
    }

    public function update(User $user, Item $item): bool
    {
        return $user->hasAnyRole(['manager', 'warehouse_staff'])
            || $user->can('items.edit');
    }

    public function delete(User $user, Item $item): bool
    {
        return $user->hasRole('manager')
            || $user->can('items.delete');
    }

    public function changeStatus(User $user, Item $item): bool
    {
        return $user->hasAnyRole(['manager', 'warehouse_staff'])
            || $user->can('items.change_status');
    }
}
