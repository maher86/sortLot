<?php

namespace Tests\Feature\Invoices;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoicePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_read_but_cannot_create_sales_order(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@sortlot.local')->firstOrFail();
        $viewer = User::factory()->create(['email' => 'invoice-viewer@sortlot.local']);
        $viewer->assignRole('viewer');
        $customer = Customer::query()->create(['name' => 'Permission Customer']);

        Sanctum::actingAs($admin);
        $this->postJson('/api/v1/sales-orders', [
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'lines' => [['description' => 'Visible line', 'quantity' => 1, 'unit_price_fils' => 10000]],
        ])->assertCreated();

        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/sales-orders')->assertOk();
        $this->postJson('/api/v1/sales-orders', [
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'lines' => [['description' => 'Forbidden line', 'quantity' => 1, 'unit_price_fils' => 10000]],
        ])->assertForbidden();
    }

    public function test_invoice_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/sales-orders')->assertUnauthorized();
        $this->getJson('/api/v1/payments')->assertUnauthorized();
    }
}
