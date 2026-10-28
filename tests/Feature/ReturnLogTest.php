<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_record_return_and_stock_is_restored_and_damaged_condition_updates_item(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $item = Item::factory()->create([
            'name' => 'Matras Senam',
            'stock' => 4,
            'condition' => 'baik',
        ]);

        $loan = Loan::factory()->create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'quantity' => 2,
            'loan_date' => now()->subDays(3)->toDateString(),
            'due_date' => now()->addDays(1)->toDateString(),
            'status' => 'dipinjam',
        ]);

        // Catat pengembalian dalam kondisi rusak ringan (RET-01, RET-03, RET-04)
        $this->actingAs($admin)
            ->post(route('loans.return.store', $loan), [
                'returned_at' => now()->toDateString(),
                'condition_on_return' => 'rusak ringan',
                'notes' => 'Terdapat sobekan kecil di bagian sudut matras.',
            ])
            ->assertRedirect(route('return-logs.index'))
            ->assertSessionHas('success');

        // Status peminjaman berubah menjadi dikembalikan & stok bertambah kembali (4 + 2 = 6)
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'dikembalikan',
        ]);

        $item->refresh();
        $this->assertEquals(6, $item->stock);
        $this->assertEquals('rusak ringan', $item->condition);

        // Satu peminjaman hanya memiliki satu catatan pengembalian (RET-02)
        $this->actingAs($admin)
            ->post(route('loans.return.store', $loan), [
                'returned_at' => now()->toDateString(),
                'condition_on_return' => 'baik',
            ])
            ->assertRedirect(route('loans.show', $loan))
            ->assertSessionHas('error');

        $this->assertEquals(1, $loan->returnLog()->count());
        $this->assertEquals(6, $item->fresh()->stock);
    }

    public function test_non_admin_cannot_access_return_logs(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'dipinjam']);

        $this->actingAs($user)->get(route('return-logs.index'))->assertForbidden();
        $this->actingAs($user)->get(route('loans.return.create', $loan))->assertForbidden();
        $this->actingAs($user)->post(route('loans.return.store', $loan), [])->assertForbidden();
    }
}

