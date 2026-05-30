<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\OverdueInvoiceNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Mark unpaid invoices past their due date as overdue and notify accounting users.';

    public function handle(): int
    {
        $marked = 0;
        $notifications = 0;

        Invoice::query()
            ->whereIn('status', [InvoiceStatus::Pending->value, InvoiceStatus::Partial->value])
            ->whereDate('due_date', '<', now()->toDateString())
            ->where('balance_fils', '>', 0)
            ->with('createdBy')
            ->chunkById(100, function (Collection $invoices) use (&$marked, &$notifications): void {
                foreach ($invoices as $invoice) {
                    $invoice->markOverdue();

                    if ($invoice->fresh()->status !== InvoiceStatus::Overdue) {
                        continue;
                    }

                    $marked++;
                    $notifications += $this->notifyUsers($invoice);
                }
            });

        $this->info("Marked {$marked} invoice(s) overdue and created {$notifications} notification(s).");

        return self::SUCCESS;
    }

    private function notifyUsers(Invoice $invoice): int
    {
        $users = User::query()
            ->role('accountant')
            ->get()
            ->when($invoice->createdBy, fn (Collection $users, User $creator) => $users->push($creator))
            ->unique('id')
            ->values();

        $users->each(fn (User $user) => $user->notify(new OverdueInvoiceNotification($invoice)));

        return $users->count();
    }
}
