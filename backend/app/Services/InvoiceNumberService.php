<?php

namespace App\Services;

use App\Enums\InvoiceType;
use App\Models\Preference;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceNumberService
{
    public function generate(InvoiceType $type): string
    {
        return DB::transaction(function () use ($type): string {
            $prefixKey = match ($type) {
                InvoiceType::SalesOrder, InvoiceType::CreditNote => 'invoice_prefix_sales',
                InvoiceType::PurchaseOrder => 'invoice_prefix_purchase',
            };
            $sequenceKey = match ($type) {
                InvoiceType::SalesOrder, InvoiceType::CreditNote => 'invoice_next_seq_sales',
                InvoiceType::PurchaseOrder => 'invoice_next_seq_purchase',
            };

            $prefix = Preference::query()->where('key', $prefixKey)->value('value');
            $sequence = Preference::query()->where('key', $sequenceKey)->lockForUpdate()->first();

            if (! $prefix || ! $sequence) {
                throw new InvalidArgumentException("Invoice numbering preference missing for {$type->value}.");
            }

            $nextSequence = max(1, (int) $sequence->value);
            $sequence->update(['value' => (string) ($nextSequence + 1)]);

            $numberPrefix = $type === InvoiceType::CreditNote ? 'CN' : $prefix;

            return sprintf('%s-%s-%05d', $numberPrefix, now()->format('Y'), $nextSequence);
        });
    }
}
