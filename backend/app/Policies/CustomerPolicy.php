<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super_admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'accountant', 'viewer'])
            || $user->can('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasAnyRole(['manager', 'accountant', 'viewer'])
            || $user->can('customers.view');
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'accountant'])
            || $user->can('customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasAnyRole(['manager', 'accountant'])
            || $user->can('customers.edit');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasRole('manager')
            || $user->can('customers.delete');
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $this->delete($user, $customer);
    }
}
