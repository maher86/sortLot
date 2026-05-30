<?php

namespace Tests\Unit\Services;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\ItemGender;
use App\Enums\ItemSeason;
use App\Enums\ItemStatus;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Package;
use App\Models\PricingTier;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_recalculates_totals_and_generates_number(): void
    {
        [$user, $customer] = $this->seededCustomer();

        $invoice = app(InvoiceService::class)->create([
            'type' => InvoiceType::SalesOrder,
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'discount_pct' => 10,
            'created_by' => $user->id,
            'lines' => [
                ['description' => 'Manual bale', 'quantity' => 2, 'unit_price_fils' => 10000],
            ],
        ]);

        $this->assertStringStartsWith('SO-'.now()->format('Y'), $invoice->number);
        $this->assertSame(20000, $invoice->subtotal_fils);
        $this->assertSame(2000, $invoice->discount_fils);
        $this->assertSame(900, $invoice->vat_amount_fils);
        $this->assertSame(18900, $invoice->total_fils);
        $this->assertSame(18900, $invoice->balance_fils);
    }

    public function test_confirm_and_cancel_reserve_and_release_sales_items(): void
    {
        [$user, $customer] = $this->seededCustomer();
        $item = $this->itemFor($user);
        $service = app(InvoiceService::class);
        $invoice = $service->create([
            'type' => InvoiceType::SalesOrder,
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'created_by' => $user->id,
            'lines' => [
                ['item_id' => $item->id, 'quantity' => 1],
            ],
        ]);

        $service->confirm($invoice);
        $this->assertSame(InvoiceStatus::Pending, $invoice->fresh()->status);
        $this->assertSame(ItemStatus::Reserved, $item->fresh()->status);
        $this->assertSame($invoice->id, $item->fresh()->sales_order_id);

        $service->cancel($invoice->fresh());
        $this->assertSame(InvoiceStatus::Cancelled, $invoice->fresh()->status);
        $this->assertSame(ItemStatus::Available, $item->fresh()->status);
        $this->assertNull($item->fresh()->sales_order_id);
    }

    public function test_generate_pdf_send_email_and_credit_note_methods(): void
    {
        [$user, $customer] = $this->seededCustomer();
        $service = app(InvoiceService::class);
        $invoice = $service->create([
            'type' => InvoiceType::SalesOrder,
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'created_by' => $user->id,
            'lines' => [
                ['description' => 'Creditable line', 'quantity' => 1, 'unit_price_fils' => 5000],
            ],
        ]);

        $path = $service->generatePdf($invoice);
        $service->sendEmail($invoice->fresh());
        $creditNote = $service->generateCreditNote($invoice->fresh());

        $this->assertStringEndsWith("{$invoice->number}.pdf", $path);
        $this->assertNotNull($invoice->fresh()->sent_at);
        $this->assertSame(InvoiceType::CreditNote, $creditNote->type);
        $this->assertSame($invoice->id, $creditNote->related_invoice_id);
    }

    /**
     * @return array{User, Customer}
     */
    private function seededCustomer(): array
    {
        $this->seed();
        $user = User::where('email', 'admin@sortlot.local')->firstOrFail();
        $customer = Customer::query()->create(['name' => 'Service Customer']);

        return [$user, $customer];
    }

    private function itemFor(User $user): Item
    {
        $package = Package::query()->create([
            'reference' => 'INV-SVC-PKG',
            'origin_country' => 'US',
            'created_by' => $user->id,
        ]);

        return Item::query()->create([
            'package_id' => $package->id,
            'season' => ItemSeason::Summer,
            'gender' => ItemGender::Woman,
            'item_type_id' => ItemType::query()->firstOrFail()->id,
            'pricing_tier_id' => PricingTier::query()->firstOrFail()->id,
            'unit_price_fils' => 15000,
        ]);
    }
}
