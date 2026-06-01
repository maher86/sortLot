<?php

namespace Tests\Unit\Services;

use App\Enums\InvoiceType;
use App\Enums\VatType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_vat_rate_depends_on_customer_vat_type(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@sortlot.local')->firstOrFail();
        $service = app(InvoiceService::class);
        $invoice = Invoice::query()->create([
            'type' => InvoiceType::SalesOrder,
            'number' => 'VAT-SO-1',
            'issue_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $mainland = Customer::query()->create(['name' => 'Mainland', 'vat_type' => VatType::Mainland]);
        $freeZone = Customer::query()->create(['name' => 'Free Zone', 'vat_type' => VatType::FreeZone]);
        $international = Customer::query()->create(['name' => 'International', 'vat_type' => VatType::International]);

        $this->assertSame(5.0, $service->calculateVat($invoice, $mainland)['rate']);
        $this->assertSame(0.0, $service->calculateVat($invoice, $freeZone)['rate']);
        $this->assertSame(0.0, $service->calculateVat($invoice, $international)['rate']);
    }

    public function test_purchase_vat_rate_depends_on_supplier_vat_type(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@sortlot.local')->firstOrFail();
        $service = app(InvoiceService::class);
        $invoice = Invoice::query()->create([
            'type' => InvoiceType::PurchaseOrder,
            'number' => 'VAT-PO-1',
            'issue_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $mainland = Supplier::query()->create(['name' => 'Mainland', 'country' => 'AE', 'vat_type' => VatType::Mainland]);
        $freeZone = Supplier::query()->create(['name' => 'Free Zone', 'country' => 'AE', 'vat_type' => VatType::FreeZone]);
        $international = Supplier::query()->create(['name' => 'International', 'country' => 'US', 'vat_type' => VatType::International]);

        $this->assertSame(5.0, $service->calculateVat($invoice, $mainland)['rate']);
        $this->assertSame(0.0, $service->calculateVat($invoice, $freeZone)['rate']);
        $this->assertSame(0.0, $service->calculateVat($invoice, $international)['rate']);
    }
}
