<?php

namespace Tests\Feature\Customers;

use App\Enums\VatType;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_list_show_update_delete_and_restore_customer(): void
    {
        $user = $this->seededUser();
        Sanctum::actingAs($user);

        $create = $this->postJson('/api/v1/customers', [
            'name' => 'Al Noor Textiles',
            'contact_name' => 'Nadia Saleh',
            'email' => 'accounts@alnoor.example',
            'phone' => '+971501112222',
            'country' => 'AE',
            'emirate' => 'Dubai',
            'address' => 'Al Quoz',
            'vat_type' => VatType::Mainland->value,
            'trn' => '100000000000001',
            'credit_limit_fils' => 250000,
            'payment_terms_days' => 30,
            'notes' => 'Wholesale customer',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Al Noor Textiles')
            ->assertJsonPath('data.vat_type', VatType::Mainland->value)
            ->assertJsonPath('data.credit_limit_fils', 250000);

        $customerId = $create->json('data.id');

        $this->getJson('/api/v1/customers?filter[vat_type]=mainland&search=Noor')
            ->assertOk()
            ->assertJsonPath('data.0.id', $customerId);

        $this->getJson("/api/v1/customers/{$customerId}")
            ->assertOk()
            ->assertJsonPath('data.email', 'accounts@alnoor.example');

        $this->patchJson("/api/v1/customers/{$customerId}", [
            'credit_limit_fils' => 300000,
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.credit_limit_fils', 300000)
            ->assertJsonPath('data.is_active', false);

        $this->deleteJson("/api/v1/customers/{$customerId}")
            ->assertNoContent();

        $this->assertSoftDeleted('customers', ['id' => $customerId]);

        $this->postJson("/api/v1/customers/{$customerId}/restore")
            ->assertOk()
            ->assertJsonPath('data.id', $customerId)
            ->assertJsonPath('data.deleted_at', null);

        $this->assertDatabaseHas('customers', [
            'id' => $customerId,
            'deleted_at' => null,
        ]);
    }

    public function test_viewer_can_read_but_cannot_create_customer(): void
    {
        $this->seed();
        $viewer = User::factory()->create(['email' => 'viewer-customers@sortlot.local']);
        $viewer->assignRole('viewer');
        Sanctum::actingAs($viewer);

        Customer::query()->create(['name' => 'Viewer Customer']);

        $this->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Viewer Customer');

        $this->postJson('/api/v1/customers', ['name' => 'Forbidden Customer'])
            ->assertForbidden();
    }

    public function test_customer_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/customers')->assertUnauthorized();
    }

    private function seededUser(): User
    {
        $this->seed();

        return User::where('email', 'admin@sortlot.local')->firstOrFail();
    }
}
