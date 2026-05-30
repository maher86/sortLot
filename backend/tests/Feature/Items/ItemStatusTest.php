<?php

namespace Tests\Feature\Items;

use App\Enums\ItemGender;
use App\Enums\ItemSeason;
use App\Enums\ItemStatus;
use App\Models\AuditLog;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Package;
use App\Models\PricingTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ItemStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_item_status_with_reason_and_audit_log(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@sortlot.local')->firstOrFail();
        Sanctum::actingAs($user);
        $item = $this->itemFor($user);

        $this->patchJson("/api/v1/items/{$item->id}/status", [
            'status' => ItemStatus::Damaged->value,
            'reason' => 'Torn during sorting',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', ItemStatus::Damaged->value)
            ->assertJsonPath('data.sorted_by', $user->id);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'status' => ItemStatus::Damaged->value,
            'sorted_by' => $user->id,
        ]);

        $auditLog = AuditLog::query()
            ->where('model_type', Item::class)
            ->where('model_id', $item->id)
            ->where('action', 'status_changed')
            ->firstOrFail();

        $this->assertSame(['status' => ItemStatus::Available->value], $auditLog->old_values);
        $this->assertSame(ItemStatus::Damaged->value, $auditLog->new_values['status']);
        $this->assertSame('Torn during sorting', $auditLog->new_values['reason']);
    }

    public function test_invalid_item_status_returns_422(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@sortlot.local')->firstOrFail();
        Sanctum::actingAs($user);
        $item = $this->itemFor($user);

        $this->patchJson("/api/v1/items/{$item->id}/status", [
            'status' => 'archived',
            'reason' => 'Invalid status',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    private function itemFor(User $user): Item
    {
        $package = Package::query()->create([
            'reference' => '2026-ITEM-STATUS',
            'origin_country' => 'US',
            'created_by' => $user->id,
        ]);

        return Item::query()->create([
            'package_id' => $package->id,
            'season' => ItemSeason::General,
            'gender' => ItemGender::Boy,
            'item_type_id' => ItemType::query()->firstOrFail()->id,
            'pricing_tier_id' => PricingTier::query()->firstOrFail()->id,
            'status' => ItemStatus::Available,
            'unit_price_fils' => 1000,
        ]);
    }
}
