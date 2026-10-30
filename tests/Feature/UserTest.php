<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_crud_users_and_activity_is_logged(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // 1. Index
        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee($admin->email);

        // 2. Store
        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Rina Permata',
                'email' => 'rina@sekolah.test',
                'role' => 'user',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $targetUser = User::where('email', 'rina@sekolah.test')->firstOrFail();
        $this->assertSame('user', $targetUser->role);
        $this->assertTrue(Hash::check('password123', $targetUser->password));
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'create_user',
        ]);

        // 3. Update tanpa ubah password
        $this->actingAs($admin)
            ->put(route('users.update', $targetUser), [
                'name' => 'Rina Permata Sari',
                'email' => 'rina@sekolah.test',
                'role' => 'admin',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $targetUser->refresh();
        $this->assertSame('Rina Permata Sari', $targetUser->name);
        $this->assertSame('admin', $targetUser->role);
        $this->assertTrue(Hash::check('password123', $targetUser->password));
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'update_user',
        ]);

        // 4. Destroy
        $this->actingAs($admin)
            ->delete(route('users.destroy', $targetUser))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'delete_user',
        ]);
    }

    public function test_admin_cannot_delete_or_demote_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Tidak bisa hapus akun sendiri
        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        // Tidak bisa turunkan role akun sendiri menjadi user
        $this->actingAs($admin)
            ->from(route('users.edit', $admin))
            ->put(route('users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'user',
            ])
            ->assertRedirect(route('users.edit', $admin))
            ->assertSessionHas('error');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_admin_cannot_delete_user_with_active_loan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $borrower = User::factory()->create(['role' => 'user']);
        $category = Category::create(['name' => 'Elektronik']);
        $item = Item::create([
            'category_id' => $category->id,
            'name' => 'Proyektor Epson',
            'code' => 'ELK-100',
            'stock' => 2,
            'condition' => 'baik',
        ]);

        Loan::create([
            'user_id' => $borrower->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
            'status' => 'dipinjam',
        ]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $borrower))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $borrower->id]);
    }

    public function test_regular_user_cannot_access_user_management(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('users.create'))->assertForbidden();
        $this->actingAs($user)->post(route('users.store'), [])->assertForbidden();
        $this->actingAs($user)->get(route('users.edit', $other))->assertForbidden();
        $this->actingAs($user)->put(route('users.update', $other), [])->assertForbidden();
        $this->actingAs($user)->delete(route('users.destroy', $other))->assertForbidden();
    }
}

