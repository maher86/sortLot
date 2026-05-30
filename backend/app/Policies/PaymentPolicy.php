<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super_admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'accountant', 'viewer'])
            || $user->can('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'accountant'])
            || $user->can('payments.create');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->hasRole('manager')
            || $user->can('payments.delete');
    }
}
