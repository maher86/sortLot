<?php

namespace Tests\Feature\Invoices;

use App\Enums\InvoiceStatus;
use App\Enums\ItemGender;
use App\Enums\ItemSeason;
use App\Enums\ItemStatus;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Package;
use App\Models\PricingTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceStatusFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_reserves_items_and_cancel_releases_them(): void
    {
        $user = $this->seededUser();
        Sanctum::actingAs($user);
        $customer = Customer::query()->create(['name' => 'Flow Customer']);
        $item = $this->itemFor($user);

        $invoiceId = $this->postJson('/api/v1/sales-orders', [
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'lines' => [['item_id' => $item->id, 'quantity' => 1]],
        ])->assertCreated()->json('data.id');

        $this->patchJson("/api/v1/sales-orders/{$invoiceId}/confirm")
            ->assertOk()
            ->assertJsonPath('data.status', InvoiceStatus::Pending->value);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'status' => ItemStatus::Reserved->value,
            'sales_order_id' => $invoiceId,
        ]);

        $this->patchJson("/api/v1/sales-orders/{$invoiceId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', InvoiceStatus::Cancelled->value);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'status' => ItemStatus::Available->value,
            'sales_order_id' => null,
        ]);
    }

    private function seededUser(): User
    {
        $this->seed();

        return User::where('email', 'admin@sortlot.local')->firstOrFail();
    }

    private function itemFor(User $user): Item
    {
        $package = Package::query()->create([
            'reference' => 'FLOW-PKG',
            'origin_country' => 'US',
            'created_by' => $user->id,
        ]);

        return Item::query()->create([
            'package_id' => $package->id,
            'season' => ItemSeason::Summer,
            'gender' => ItemGender::Woman,
            'item_type_id' => ItemType::query()->firstOrFail()->id,
            'pricing_tier_id' => PricingTier::query()->firstOrFail()->id,
            'unit_price_fils' => 10000,
        ]);
    }
}
