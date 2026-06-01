<?php

namespace Tests\Feature\Invoices;

use App\Enums\InvoiceStatus;
use App\Enums\VatType;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseOrderCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_list_show_update_and_delete_draft_purchase_order(): void
    {
        $user = $this->seededUser();
        Sanctum::actingAs($user);
        $supplier = Supplier::query()->create([
            'name' => 'Purchase Supplier',
            'country' => 'AE',
            'vat_type' => VatType::FreeZone,
        ]);

        $create = $this->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->id,
            'issue_date' => now()->toDateString(),
            'lines' => [
                ['description' => 'Inbound bale lot', 'quantity' => 3, 'unit_price_fils' => 20000],
            ],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.supplier_id', $supplier->id)
            ->assertJsonPath('data.status', InvoiceStatus::Draft->value)
            ->assertJsonPath('data.subtotal_fils', 60000)
            ->assertJsonPath('data.vat_amount_fils', 0)
            ->assertJsonPath('data.total_fils', 60000);

        $invoiceId = $create->json('data.id');

        $this->getJson('/api/v1/purchase-orders')
            ->assertOk()
            ->assertJsonPath('data.0.id', $invoiceId);

        $this->getJson("/api/v1/purchase-orders/{$invoiceId}")
            ->assertOk()
            ->assertJsonPath('data.lines.0.description', 'Inbound bale lot');

        $this->patchJson("/api/v1/purchase-orders/{$invoiceId}", ['reference' => 'SUP-PO-1'])
            ->assertOk()
            ->assertJsonPath('data.reference', 'SUP-PO-1');

        $this->deleteJson("/api/v1/purchase-orders/{$invoiceId}")
            ->assertNoContent();
    }

    private function seededUser(): User
    {
        $this->seed();

        return User::where('email', 'admin@sortlot.local')->firstOrFail();
    }
}
