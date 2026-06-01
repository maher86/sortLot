<?php

namespace Tests\Feature\Invoices;

use App\Enums\InvoiceStatus;
use App\Enums\ItemGender;
use App\Enums\ItemSeason;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Package;
use App\Models\PricingTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SalesOrderCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_list_show_update_and_delete_draft_sales_order(): void
    {
        $user = $this->seededUser();
        Sanctum::actingAs($user);
        $customer = Customer::query()->create(['name' => 'Sales Customer']);
        $item = $this->itemFor($user);

        $create = $this->postJson('/api/v1/sales-orders', [
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'lines' => [
                ['item_id' => $item->id, 'quantity' => 1],
            ],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.customer_id', $customer->id)
            ->assertJsonPath('data.status', InvoiceStatus::Draft->value)
            ->assertJsonPath('data.subtotal_fils', 12000)
            ->assertJsonPath('data.vat_amount_fils', 600)
            ->assertJsonPath('data.total_fils', 12600);

        $invoiceId = $create->json('data.id');

        $this->getJson('/api/v1/sales-orders?search='.$create->json('data.number'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $invoiceId);

        $this->getJson("/api/v1/sales-orders/{$invoiceId}")
            ->assertOk()
            ->assertJsonPath('data.lines.0.item_id', $item->id);

        $this->patchJson("/api/v1/sales-orders/{$invoiceId}", ['notes' => 'Updated sales order'])
            ->assertOk()
            ->assertJsonPath('data.notes', 'Updated sales order');

        $this->deleteJson("/api/v1/sales-orders/{$invoiceId}")
            ->assertNoContent();

        $this->assertSoftDeleted('invoices', ['id' => $invoiceId]);
    }

    private function seededUser(): User
    {
        $this->seed();

        return User::where('email', 'admin@sortlot.local')->firstOrFail();
    }

    private function itemFor(User $user): Item
    {
        $package = Package::query()->create([
            'reference' => 'SO-CRUD-PKG',
            'origin_country' => 'US',
            'created_by' => $user->id,
        ]);

        return Item::query()->create([
            'package_id' => $package->id,
            'season' => ItemSeason::Summer,
            'gender' => ItemGender::Woman,
            'item_type_id' => ItemType::query()->firstOrFail()->id,
            'pricing_tier_id' => PricingTier::query()->firstOrFail()->id,
            'unit_price_fils' => 12000,
        ]);
    }
}
