<?php

namespace Tests\Feature\Invoices;

use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreditNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_note_can_be_generated_from_sales_order(): void
    {
        $this->seed();
        Sanctum::actingAs(User::where('email', 'admin@sortlot.local')->firstOrFail());
        $customer = Customer::query()->create(['name' => 'Credit Customer']);

        $invoiceId = $this->postJson('/api/v1/sales-orders', [
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'lines' => [['description' => 'Credit source', 'quantity' => 1, 'unit_price_fils' => 10000]],
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/sales-orders/{$invoiceId}/credit-note")
            ->assertCreated()
            ->assertJsonPath('data.type', InvoiceType::CreditNote->value)
            ->assertJsonPath('data.related_invoice_id', $invoiceId)
            ->assertJsonPath('data.lines.0.description', 'Credit: Credit source');
    }
}
