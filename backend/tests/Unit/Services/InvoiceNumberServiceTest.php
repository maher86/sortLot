<?php

namespace Tests\Unit\Services;

use App\Enums\InvoiceType;
use App\Models\Preference;
use App\Services\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_sequential_sales_order_numbers(): void
    {
        $this->seed();
        $service = app(InvoiceNumberService::class);

        $first = $service->generate(InvoiceType::SalesOrder);
        $second = $service->generate(InvoiceType::SalesOrder);

        $this->assertSame('SO-'.now()->format('Y').'-00001', $first);
        $this->assertSame('SO-'.now()->format('Y').'-00002', $second);
        $this->assertSame('3', Preference::where('key', 'invoice_next_seq_sales')->value('value'));
    }

    public function test_purchase_orders_use_their_own_sequence(): void
    {
        $this->seed();
        $service = app(InvoiceNumberService::class);

        $this->assertSame('PO-'.now()->format('Y').'-00001', $service->generate(InvoiceType::PurchaseOrder));
        $this->assertSame('1', Preference::where('key', 'invoice_next_seq_sales')->value('value'));
        $this->assertSame('2', Preference::where('key', 'invoice_next_seq_purchase')->value('value'));
    }
}
