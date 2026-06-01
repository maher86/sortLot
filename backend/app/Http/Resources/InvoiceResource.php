<?php

namespace App\Http\Resources;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Invoice */
class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'number' => $this->number,
            'reference' => $this->reference,
            'status' => $this->status->value,
            'customer_id' => $this->customer_id,
            'supplier_id' => $this->supplier_id,
            'related_invoice_id' => $this->related_invoice_id,
            'issue_date' => $this->issue_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'delivery_date' => $this->delivery_date?->toDateString(),
            'subtotal_fils' => $this->subtotal_fils,
            'discount_fils' => $this->discount_fils,
            'discount_pct' => $this->discount_pct,
            'vat_rate' => $this->vat_rate,
            'vat_amount_fils' => $this->vat_amount_fils,
            'total_fils' => $this->total_fils,
            'paid_amount_fils' => $this->paid_amount_fils,
            'balance_fils' => $this->balance_fils,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'notes' => $this->notes,
            'internal_notes' => $this->internal_notes,
            'terms' => $this->terms,
            'pdf_path' => $this->pdf_path,
            'pdf_generated_at' => $this->pdf_generated_at?->toISOString(),
            'sent_at' => $this->sent_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'customer' => CustomerResource::make($this->whenLoaded('customer')),
            'supplier' => SupplierResource::make($this->whenLoaded('supplier')),
            'lines' => InvoiceLineResource::collection($this->whenLoaded('lines')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
