<?php

namespace App\Jobs;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\VatType;
use App\Models\Invoice;
use App\Models\Preference;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GenerateInvoicePdfJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $invoiceId) {}

    public function handle(): void
    {
        $invoice = Invoice::query()
            ->with(['customer', 'supplier', 'lines.item', 'payments'])
            ->findOrFail($this->invoiceId);

        $path = $invoice->pdf_path ?: $this->pathFor($invoice);
        $party = $invoice->customer ?? $invoice->supplier;
        $isTaxable = $party?->vat_type === VatType::Mainland;
        $company = Preference::query()->pluck('value', 'key');

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => $company,
            'party' => $party,
            'taxLabel' => $isTaxable ? 'Tax Invoice' : 'Zero-Rated Supply',
            'isPaid' => $invoice->status === InvoiceStatus::Paid,
        ])->setPaper('a4');

        Storage::disk(config('filesystems.default'))->put($path, $pdf->output());

        $invoice->forceFill([
            'pdf_path' => $path,
            'pdf_generated_at' => now(),
        ])->save();
    }

    private function pathFor(Invoice $invoice): string
    {
        $prefix = $invoice->type === InvoiceType::PurchaseOrder ? 'purchase-orders' : 'sales-orders';

        return sprintf(
            'invoices/%s/%s/%s/%s.pdf',
            now()->format('Y'),
            now()->format('m'),
            $prefix,
            $invoice->number,
        );
    }
}
