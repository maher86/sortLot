<?php

namespace App\Policies;

use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super_admin') ? true : null;
    }

    public function viewSales(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'accountant', 'viewer'])
            || $user->can('sales_orders.view');
    }

    public function viewPurchase(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'accountant', 'viewer'])
            || $user->can('purchase_orders.view');
    }

    public function createSales(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'accountant'])
            || $user->can('sales_orders.create');
    }

    public function createPurchase(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'accountant'])
            || $user->can('purchase_orders.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->usesSalesPermission($invoice)
            ? ($user->hasAnyRole(['manager', 'accountant']) || $user->can('sales_orders.edit'))
            : ($user->hasAnyRole(['manager', 'accountant']) || $user->can('purchase_orders.edit'));
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->usesSalesPermission($invoice)
            ? ($user->hasRole('manager') || $user->can('sales_orders.delete'))
            : ($user->hasRole('manager') || $user->can('purchase_orders.delete'));
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->usesSalesPermission($invoice)
            ? $this->viewSales($user)
            : $this->viewPurchase($user);
    }

    public function confirm(User $user, Invoice $invoice): bool
    {
        return $this->usesSalesPermission($invoice)
            ? ($user->hasAnyRole(['manager', 'accountant']) || $user->can('sales_orders.confirm'))
            : ($user->hasAnyRole(['manager', 'accountant']) || $user->can('purchase_orders.confirm'));
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        return $this->usesSalesPermission($invoice)
            ? ($user->hasRole('manager') || $user->can('sales_orders.cancel'))
            : ($user->hasRole('manager') || $user->can('purchase_orders.delete'));
    }

    private function usesSalesPermission(Invoice $invoice): bool
    {
        return in_array($invoice->type, [InvoiceType::SalesOrder, InvoiceType::CreditNote], true);
    }
}
