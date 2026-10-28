<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_user_can_view_and_filter_items(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $category = Category::factory()->create(['name' => 'Elektronik']);

        $availableItem = Item::factory()->create([
            'category_id' => $category->id,
            'name' => 'Proyektor LCD',
            'code' => 'ELK-001',
            'stock' => 4,
            'condition' => 'baik',
        ]);

        $damagedItem = Item::factory()->create([
            'category_id' => $category->id,
            'name' => 'Proyektor Lama',
            'code' => 'ELK-002',
            'stock' => 1,
            'condition' => 'rusak berat',
        ]);

        // Admin index
        $this->actingAs($admin)
            ->get(route('items.index'))
            ->assertOk()
            ->assertSee('Proyektor LCD')
            ->assertSee('Proyektor Lama');

        // User index + filter condition=baik + tombol Ajukan Peminjaman
        $this->actingAs($user)
            ->get(route('items.index', ['condition' => 'baik']))
            ->assertOk()
            ->assertSee('Proyektor LCD')
            ->assertDontSee('Proyektor Lama')
            ->assertSee('Ajukan Peminjaman');

        // Detail page
        $this->actingAs($user)
            ->get(route('items.show', $availableItem))
            ->assertOk()
            ->assertSee('ELK-001');
    }

    public function test_non_admin_cannot_create_update_or_delete_items(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $item = Item::factory()->create();

        $this->actingAs($user)->get(route('items.create'))->assertForbidden();
        $this->actingAs($user)->post(route('items.store'), [])->assertForbidden();
        $this->actingAs($user)->get(route('items.edit', $item))->assertForbidden();
        $this->actingAs($user)->put(route('items.update', $item), [])->assertForbidden();
        $this->actingAs($user)->delete(route('items.destroy', $item))->assertForbidden();
    }

    public function test_admin_can_create_update_and_soft_delete_item(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create(['name' => 'Alat Laboratorium']);

        // Create
        $this->actingAs($admin)
            ->post(route('items.store'), [
                'category_id' => $category->id,
                'name' => 'Mikroskop Binokuler',
                'code' => 'LAB-001',
                'stock' => 10,
                'condition' => 'baik',
            ])
            ->assertRedirect(route('items.index'))
            ->assertSessionHas('success');

        $item = Item::where('code', 'LAB-001')->firstOrFail();
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'create_item',
        ]);

        // Update
        $this->actingAs($admin)
            ->put(route('items.update', $item), [
                'category_id' => $category->id,
                'name' => 'Mikroskop Digital',
                'code' => 'LAB-001',
                'stock' => 8,
                'condition' => 'rusak ringan',
            ])
            ->assertRedirect(route('items.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Mikroskop Digital',
            'stock' => 8,
            'condition' => 'rusak ringan',
        ]);

        // Soft Delete
        $this->actingAs($admin)
            ->delete(route('items.destroy', $item))
            ->assertRedirect(route('items.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('items', ['id' => $item->id]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'delete_item',
        ]);
    }
}

