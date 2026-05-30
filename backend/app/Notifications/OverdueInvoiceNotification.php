<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OverdueInvoiceNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Invoice $invoice) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'invoice_type' => $this->invoice->type->value,
            'customer_id' => $this->invoice->customer_id,
            'supplier_id' => $this->invoice->supplier_id,
            'due_date' => $this->invoice->due_date?->toDateString(),
            'balance_fils' => $this->invoice->balance_fils,
            'message' => "Invoice {$this->invoice->number} is overdue.",
        ];
    }
}
