<?php

namespace Tests\Feature\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\OverdueInvoiceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MarkOverdueInvoicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_marks_due_pending_and_partial_invoices_overdue_and_notifies_users(): void
    {
        $this->seed();
        $creator = User::where('email', 'admin@sortlot.local')->firstOrFail();
        $accountant = User::factory()->create(['email' => 'accountant@sortlot.local']);
        $accountant->assignRole('accountant');
        $customer = Customer::query()->create(['name' => 'Overdue Customer']);

        $pending = $this->invoice([
            'number' => 'SO-OVERDUE-001',
            'status' => InvoiceStatus::Pending,
            'customer_id' => $customer->id,
            'due_date' => now()->subDay()->toDateString(),
            'balance_fils' => 10000,
            'created_by' => $creator->id,
        ]);
        $partial = $this->invoice([
            'number' => 'SO-OVERDUE-002',
            'status' => InvoiceStatus::Partial,
            'customer_id' => $customer->id,
            'due_date' => now()->subDays(2)->toDateString(),
            'balance_fils' => 5000,
            'created_by' => $creator->id,
        ]);
        $future = $this->invoice([
            'number' => 'SO-FUTURE-001',
            'status' => InvoiceStatus::Pending,
            'customer_id' => $customer->id,
            'due_date' => now()->addDay()->toDateString(),
            'balance_fils' => 10000,
            'created_by' => $creator->id,
        ]);
        $paid = $this->invoice([
            'number' => 'SO-PAID-001',
            'status' => InvoiceStatus::Paid,
            'customer_id' => $customer->id,
            'due_date' => now()->subDay()->toDateString(),
            'balance_fils' => 0,
            'created_by' => $creator->id,
        ]);

        $exitCode = Artisan::call('invoices:mark-overdue');

        $this->assertSame(0, $exitCode);
        $this->assertSame(InvoiceStatus::Overdue, $pending->fresh()->status);
        $this->assertSame(InvoiceStatus::Overdue, $partial->fresh()->status);
        $this->assertSame(InvoiceStatus::Pending, $future->fresh()->status);
        $this->assertSame(InvoiceStatus::Paid, $paid->fresh()->status);

        $this->assertDatabaseCount('notifications', 4);
        $this->assertDatabaseHas('notifications', [
            'type' => OverdueInvoiceNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $creator->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'type' => OverdueInvoiceNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $accountant->id,
        ]);
        $this->assertStringContainsString('Marked 2 invoice(s) overdue', Artisan::output());
    }

    public function test_command_is_registered_in_schedule(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('invoices:mark-overdue')
            ->assertSuccessful();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function invoice(array $attributes): Invoice
    {
        return Invoice::query()->create([
            'type' => InvoiceType::SalesOrder,
            'issue_date' => now()->subDays(10)->toDateString(),
            'total_fils' => $attributes['balance_fils'],
            'paid_amount_fils' => 0,
            ...$attributes,
        ]);
    }
}
