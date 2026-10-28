<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_categories_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Category::factory()->create(['name' => 'Alat Laboratorium']);

        $response = $this->actingAs($admin)->get(route('categories.index'));

        $response->assertOk();
        $response->assertSee('Alat Laboratorium');
    }

    public function test_non_admin_cannot_access_categories(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('categories.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_empty_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create
        $this->actingAs($admin)
            ->post(route('categories.store'), [
                'name' => 'Alat Musik',
                'description' => 'Peralatan musik ekstrakurikuler',
            ])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $category = Category::where('name', 'Alat Musik')->firstOrFail();
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'create_category',
        ]);

        // Update
        $this->actingAs($admin)
            ->put(route('categories.update', $category), [
                'name' => 'Alat Kesenian',
                'description' => 'Peralatan seni dan musik',
            ])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Alat Kesenian']);

        // Delete empty category
        $this->actingAs($admin)
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_with_items_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create(['name' => 'Elektronik']);
        Item::factory()->create(['category_id' => $category->id]);

        $this->actingAs($admin)
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}

