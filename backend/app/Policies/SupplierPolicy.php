<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super_admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'accountant', 'viewer'])
            || $user->can('suppliers.view');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->hasAnyRole(['manager', 'accountant', 'viewer'])
            || $user->can('suppliers.view');
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'accountant'])
            || $user->can('suppliers.create');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->hasAnyRole(['manager', 'accountant'])
            || $user->can('suppliers.edit');
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->hasRole('manager')
            || $user->can('suppliers.delete');
    }

    public function restore(User $user, Supplier $supplier): bool
    {
        return $this->delete($user, $supplier);
    }
}
