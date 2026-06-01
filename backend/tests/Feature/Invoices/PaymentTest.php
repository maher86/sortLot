<?php

namespace Tests\Feature\Invoices;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_updates_invoice_balance_and_status(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@sortlot.local')->firstOrFail();
        Sanctum::actingAs($user);
        $customer = Customer::query()->create(['name' => 'Payment Customer']);

        $invoiceId = $this->postJson('/api/v1/sales-orders', [
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'lines' => [['description' => 'Paid line', 'quantity' => 1, 'unit_price_fils' => 10000]],
        ])->assertCreated()->json('data.id');

        $this->patchJson("/api/v1/sales-orders/{$invoiceId}/confirm")->assertOk();

        $this->postJson('/api/v1/payments', [
            'invoice_id' => $invoiceId,
            'amount_fils' => 5000,
            'payment_method' => PaymentMethod::Cash->value,
            'payment_date' => now()->toDateString(),
        ])->assertCreated();

        $this->getJson("/api/v1/sales-orders/{$invoiceId}")
            ->assertOk()
            ->assertJsonPath('data.paid_amount_fils', 5000)
            ->assertJsonPath('data.balance_fils', 5500)
            ->assertJsonPath('data.status', InvoiceStatus::Partial->value);

        $this->postJson('/api/v1/payments', [
            'invoice_id' => $invoiceId,
            'amount_fils' => 5500,
            'payment_method' => PaymentMethod::BankTransfer->value,
            'payment_date' => now()->toDateString(),
        ])->assertCreated();

        $this->getJson("/api/v1/sales-orders/{$invoiceId}")
            ->assertOk()
            ->assertJsonPath('data.paid_amount_fils', 10500)
            ->assertJsonPath('data.balance_fils', 0)
            ->assertJsonPath('data.status', InvoiceStatus::Paid->value);
    }
}
