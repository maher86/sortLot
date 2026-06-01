<?php

namespace Tests\Feature\Preferences;

use App\Models\ItemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ItemTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_crud_item_types(): void
    {
        $this->seed();
        $manager = User::factory()->create(['email' => 'types-manager@example.test']);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $create = $this->postJson('/api/v1/preferences/item-types', [
            'name' => 'Blazer',
            'sort_order' => 30,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Blazer')
            ->assertJsonPath('data.slug', 'blazer');

        $id = $create->json('data.id');

        $this->getJson('/api/v1/preferences/item-types')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'blazer']);

        $this->patchJson("/api/v1/preferences/item-types/{$id}", [
            'name' => 'Formal Blazer',
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Formal Blazer')
            ->assertJsonPath('data.slug', 'formal-blazer')
            ->assertJsonPath('data.is_active', false);

        $this->deleteJson("/api/v1/preferences/item-types/{$id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('item_types', ['id' => $id]);
    }

    public function test_viewer_can_list_but_cannot_create_item_types(): void
    {
        $this->seed();
        $viewer = User::factory()->create(['email' => 'types-viewer@example.test']);
        $viewer->assignRole('viewer');
        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/preferences/item-types')
            ->assertOk()
            ->assertJsonFragment(['slug' => ItemType::query()->firstOrFail()->slug]);

        $this->postJson('/api/v1/preferences/item-types', [
            'name' => 'Not Allowed',
        ])
            ->assertForbidden();
    }
}
