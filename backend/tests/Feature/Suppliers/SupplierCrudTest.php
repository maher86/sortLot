<?php

namespace Tests\Feature\Suppliers;

use App\Enums\VatType;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupplierCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_list_show_update_delete_and_restore_supplier(): void
    {
        $user = $this->seededUser();
        Sanctum::actingAs($user);

        $create = $this->postJson('/api/v1/suppliers', [
            'name' => 'Green Bale Exporters',
            'contact_name' => 'Carlos Vega',
            'email' => 'ops@greenbale.example',
            'phone' => '+1555010000',
            'country' => 'US',
            'address' => 'Port district',
            'vat_type' => VatType::International->value,
            'trn' => 'US-EXPORT-1',
            'bank_name' => 'Trade Bank',
            'bank_iban' => 'US00GREENBALE',
            'bank_swift' => 'GREENUS1',
            'notes' => 'Primary supplier',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Green Bale Exporters')
            ->assertJsonPath('data.country', 'US')
            ->assertJsonPath('data.vat_type', VatType::International->value);

        $supplierId = $create->json('data.id');

        $this->getJson('/api/v1/suppliers?filter[is_active]=1&search=Green')
            ->assertOk()
            ->assertJsonPath('data.0.id', $supplierId);

        $this->getJson("/api/v1/suppliers/{$supplierId}")
            ->assertOk()
            ->assertJsonPath('data.email', 'ops@greenbale.example');

        $this->patchJson("/api/v1/suppliers/{$supplierId}", [
            'bank_swift' => 'GREENUS2',
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.bank_swift', 'GREENUS2')
            ->assertJsonPath('data.is_active', false);

        $this->deleteJson("/api/v1/suppliers/{$supplierId}")
            ->assertNoContent();

        $this->assertSoftDeleted('suppliers', ['id' => $supplierId]);

        $this->postJson("/api/v1/suppliers/{$supplierId}/restore")
            ->assertOk()
            ->assertJsonPath('data.id', $supplierId)
            ->assertJsonPath('data.deleted_at', null);
    }

    public function test_viewer_can_read_but_cannot_create_supplier(): void
    {
        $this->seed();
        $viewer = User::factory()->create(['email' => 'viewer-suppliers@sortlot.local']);
        $viewer->assignRole('viewer');
        Sanctum::actingAs($viewer);

        Supplier::query()->create([
            'name' => 'Viewer Supplier',
            'country' => 'AE',
        ]);

        $this->getJson('/api/v1/suppliers')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Viewer Supplier');

        $this->postJson('/api/v1/suppliers', [
            'name' => 'Forbidden Supplier',
            'country' => 'AE',
        ])->assertForbidden();
    }

    public function test_supplier_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/suppliers')->assertUnauthorized();
    }

    private function seededUser(): User
    {
        $this->seed();

        return User::where('email', 'admin@sortlot.local')->firstOrFail();
    }
}
