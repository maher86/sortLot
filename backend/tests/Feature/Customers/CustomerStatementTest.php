<?php

namespace Tests\Feature\Customers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerStatementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_statement_returns_balance_and_credit_limit_warning(): void
    {
        $this->seed();
        Sanctum::actingAs(User::where('email', 'admin@sortlot.local')->firstOrFail());

        $customer = Customer::query()->create([
            'name' => 'Statement Customer',
            'credit_limit_fils' => 100000,
            'payment_terms_days' => 15,
        ]);

        $this->getJson("/api/v1/customers/{$customer->id}/statement?projected_invoice_fils=120000")
            ->assertOk()
            ->assertJsonPath('data.customer.id', $customer->id)
            ->assertJsonPath('data.balance_fils', 0)
            ->assertJsonPath('data.credit_limit_fils', 100000)
            ->assertJsonPath('data.available_credit_fils', 100000)
            ->assertJsonPath('data.projected_invoice_fils', 120000)
            ->assertJsonPath('data.would_exceed_credit_limit', true)
            ->assertJsonPath('data.invoices', []);

        $this->getJson("/api/v1/customers/{$customer->id}/statement?projected_invoice_fils=50000")
            ->assertOk()
            ->assertJsonPath('data.would_exceed_credit_limit', false);
    }

    public function test_customer_without_credit_limit_never_warns(): void
    {
        $this->seed();
        Sanctum::actingAs(User::where('email', 'admin@sortlot.local')->firstOrFail());

        $customer = Customer::query()->create(['name' => 'No Limit Customer']);

        $this->getJson("/api/v1/customers/{$customer->id}/statement?projected_invoice_fils=999999")
            ->assertOk()
            ->assertJsonPath('data.credit_limit_fils', 0)
            ->assertJsonPath('data.would_exceed_credit_limit', false);
    }
}
