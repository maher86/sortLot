<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Payment */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'amount_fils' => $this->amount_fils,
            'payment_method' => $this->payment_method->value,
            'payment_date' => $this->payment_date?->toDateString(),
            'reference' => $this->reference,
            'bank_name' => $this->bank_name,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'invoice' => InvoiceResource::make($this->whenLoaded('invoice')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
