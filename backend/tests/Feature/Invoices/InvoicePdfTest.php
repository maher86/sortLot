<?php

namespace Tests\Feature\Invoices;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_pdf_generation_is_queued_and_file_exists(): void
    {
        Storage::fake('s3');
        config(['filesystems.default' => 's3']);
        config(['queue.default' => 'sync']);
        $this->seed();
        Sanctum::actingAs(User::where('email', 'admin@sortlot.local')->firstOrFail());
        $customer = Customer::query()->create([
            'name' => 'PDF Customer',
            'trn' => '100000000000009',
        ]);

        $invoiceId = $this->postJson('/api/v1/sales-orders', [
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'terms' => 'Payment within 30 days.',
            'notes' => 'PDF test invoice.',
            'lines' => [
                ['description' => 'PDF line item', 'quantity' => 2, 'unit_price_fils' => 12500],
            ],
        ])->assertCreated()->json('data.id');

        $this->getJson("/api/v1/invoices/{$invoiceId}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $invoice = Invoice::query()->findOrFail($invoiceId);

        $this->assertNotNull($invoice->pdf_path);
        $this->assertNotNull($invoice->pdf_generated_at);
        Storage::disk('s3')->assertExists($invoice->pdf_path);
        $this->assertGreaterThan(1000, strlen(Storage::disk('s3')->get($invoice->pdf_path)));
    }
}
