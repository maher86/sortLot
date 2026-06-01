<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\ItemStatus;
use App\Enums\VatType;
use App\Jobs\GenerateInvoicePdfJob;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Item;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceService
{
    public function __construct(private readonly InvoiceNumberService $invoiceNumberService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data): Invoice {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);

            $type = $data['type'] instanceof InvoiceType ? $data['type'] : InvoiceType::from($data['type']);
            $party = $this->partyFor($type, $data);
            $vat = $this->calculateVatForType($type, $party);

            $invoice = Invoice::query()->create([
                ...$data,
                'type' => $type,
                'number' => $data['number'] ?? $this->invoiceNumberService->generate($type),
                'status' => $data['status'] ?? InvoiceStatus::Draft,
                'vat_rate' => $vat['rate'],
            ]);

            foreach ($lines as $line) {
                $this->createLine($invoice, $line);
            }

            $invoice->recalculate();

            return $invoice->fresh(['customer', 'supplier', 'lines.item', 'payments']);
        });
    }

    public function confirm(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            $invoice->load('lines.item');

            if ($invoice->type === InvoiceType::SalesOrder) {
                foreach ($invoice->lines as $line) {
                    if (! $line->item_id) {
                        continue;
                    }

                    $item = Item::query()->lockForUpdate()->findOrFail($line->item_id);

                    if ($item->status !== ItemStatus::Available && $item->sales_order_id !== $invoice->id) {
                        throw new InvalidArgumentException("Cannot confirm invoice: item {$item->sku} is not available.");
                    }
                }

                foreach ($invoice->lines as $line) {
                    if ($line->item_id) {
                        Item::query()
                            ->whereKey($line->item_id)
                            ->update([
                                'status' => ItemStatus::Reserved->value,
                                'sales_order_id' => $invoice->id,
                            ]);
                    }
                }
            }

            $invoice->transitionTo(InvoiceStatus::Pending);
        });
    }

    public function cancel(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            if ($invoice->type === InvoiceType::SalesOrder) {
                Item::query()
                    ->where('sales_order_id', $invoice->id)
                    ->where('status', ItemStatus::Reserved->value)
                    ->update([
                        'status' => ItemStatus::Available,
                        'sales_order_id' => null,
                    ]);
            }

            $invoice->transitionTo(InvoiceStatus::Cancelled);
        });
    }

    public function generatePdf(Invoice $invoice): string
    {
        $path = sprintf('invoices/%s/%s/%s.pdf', now()->format('Y'), now()->format('m'), $invoice->number);

        $invoice->forceFill([
            'pdf_path' => $path,
            'pdf_generated_at' => null,
        ])->save();

        GenerateInvoicePdfJob::dispatch($invoice->id);

        return $path;
    }

    public function sendEmail(Invoice $invoice): void
    {
        $invoice->forceFill(['sent_at' => now()])->save();
    }

    /**
     * @return array{rate: float, label: string}
     */
    public function calculateVat(Invoice $invoice, Customer|Supplier $party): array
    {
        return $this->calculateVatForType($invoice->type, $party);
    }

    public function generateCreditNote(Invoice $original): Invoice
    {
        $original->load('lines');

        return $this->create([
            'type' => InvoiceType::CreditNote,
            'reference' => "Credit for {$original->number}",
            'customer_id' => $original->customer_id,
            'supplier_id' => $original->supplier_id,
            'related_invoice_id' => $original->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'discount_fils' => 0,
            'discount_pct' => 0,
            'currency' => $original->currency,
            'exchange_rate' => $original->exchange_rate,
            'notes' => "Credit note for invoice {$original->number}",
            'created_by' => $original->created_by,
            'lines' => $original->lines->map(fn (InvoiceLine $line): array => [
                'item_id' => $line->item_id,
                'description' => "Credit: {$line->description}",
                'quantity' => $line->quantity,
                'unit_price_fils' => $line->unit_price_fils,
                'discount_pct' => $line->discount_pct,
                'sort_order' => $line->sort_order,
            ])->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function createLine(Invoice $invoice, array $line): InvoiceLine
    {
        if (($line['item_id'] ?? null) && empty($line['description'])) {
            $item = Item::query()->with(['itemType'])->findOrFail($line['item_id']);
            $line['description'] = $item->itemType?->name
                ? "{$item->sku} - {$item->itemType->name}"
                : $item->sku;
            $line['unit_price_fils'] ??= $item->unit_price_fils;
        }

        return $invoice->lines()->create($line);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function partyFor(InvoiceType $type, array $data): Customer|Supplier
    {
        return match ($type) {
            InvoiceType::SalesOrder, InvoiceType::CreditNote => Customer::query()->findOrFail($data['customer_id']),
            InvoiceType::PurchaseOrder => Supplier::query()->findOrFail($data['supplier_id']),
        };
    }

    /**
     * @return array{rate: float, label: string}
     */
    private function calculateVatForType(InvoiceType $type, Customer|Supplier $party): array
    {
        $isTaxable = $party->vat_type === VatType::Mainland;

        return [
            'rate' => $isTaxable ? 5.00 : 0.00,
            'label' => $isTaxable
                ? ($type === InvoiceType::PurchaseOrder ? 'input_vat' : 'tax_invoice')
                : 'zero_rated_supply',
        ];
    }
}
