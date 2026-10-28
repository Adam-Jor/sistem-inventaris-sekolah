<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_loan_and_validation_enforces_stock_and_dates(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $item = Item::factory()->create([
            'name' => 'Proyektor LCD',
            'stock' => 3,
            'condition' => 'baik',
        ]);

        // Gagal jika jumlah melebihi stok (LOA-02)
        $this->actingAs($user)
            ->post(route('loans.store'), [
                'item_id' => $item->id,
                'quantity' => 5,
                'loan_date' => now()->toDateString(),
                'due_date' => now()->addDays(2)->toDateString(),
            ])
            ->assertSessionHasErrors('quantity');

        // Gagal jika due_date sebelum loan_date (LOA-02)
        $this->actingAs($user)
            ->post(route('loans.store'), [
                'item_id' => $item->id,
                'quantity' => 2,
                'loan_date' => now()->toDateString(),
                'due_date' => now()->subDay()->toDateString(),
            ])
            ->assertSessionHasErrors('due_date');

        // Berhasil mengajukan peminjaman (LOA-01)
        $this->actingAs($user)
            ->post(route('loans.store'), [
                'item_id' => $item->id,
                'quantity' => 2,
                'loan_date' => now()->toDateString(),
                'due_date' => now()->addDays(2)->toDateString(),
                'notes' => 'Praktikum Fisika',
            ])
            ->assertRedirect(route('loans.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('loans', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'quantity' => 2,
            'status' => 'menunggu',
        ]);
    }

    public function test_admin_can_approve_and_reject_loans_and_stock_is_updated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $item = Item::factory()->create(['stock' => 5, 'condition' => 'baik']);

        $loanToApprove = Loan::factory()->create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'quantity' => 2,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
            'status' => 'menunggu',
        ]);

        // Setujui peminjaman (LOA-03 & LOA-04)
        $this->actingAs($admin)
            ->patch(route('loans.approve', $loanToApprove))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('loans', [
            'id' => $loanToApprove->id,
            'status' => 'dipinjam',
        ]);
        $this->assertEquals(3, $item->fresh()->stock);

        // Tolak peminjaman (LOA-03)
        $loanToReject = Loan::factory()->create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'status' => 'menunggu',
        ]);

        $this->actingAs($admin)
            ->patch(route('loans.reject', $loanToReject), ['rejection_reason' => 'Dipakai kegiatan sekolah'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('loans', [
            'id' => $loanToReject->id,
            'status' => 'ditolak',
        ]);
        $this->assertEquals(3, $item->fresh()->stock);
    }

    public function test_overdue_loans_are_marked_automatically_and_user_isolation_is_enforced(): void
    {
        $userA = User::factory()->create(['role' => 'user', 'name' => 'Budi']);
        $userB = User::factory()->create(['role' => 'user', 'name' => 'Siti']);
        $item = Item::factory()->create(['name' => 'Mikroskop', 'stock' => 5, 'condition' => 'baik']);

        $overdueLoan = Loan::factory()->create([
            'user_id' => $userA->id,
            'item_id' => $item->id,
            'loan_date' => now()->subDays(5)->toDateString(),
            'due_date' => now()->subDays(1)->toDateString(),
            'status' => 'dipinjam',
        ]);

        $otherUserLoan = Loan::factory()->create([
            'user_id' => $userB->id,
            'item_id' => $item->id,
            'status' => 'menunggu',
        ]);

        // Saat User A membuka daftar peminjaman, pinjamannya yang lewat jatuh tempo berubah menjadi terlambat (LOA-05)
        $this->actingAs($userA)
            ->get(route('loans.index'))
            ->assertOk();

        $this->assertDatabaseHas('loans', [
            'id' => $overdueLoan->id,
            'status' => 'terlambat',
        ]);

        // User A tidak boleh mengakses detail peminjaman milik User B (LOA-06)
        $this->actingAs($userA)
            ->get(route('loans.show', $otherUserLoan))
            ->assertForbidden();
    }

    public function test_admin_can_soft_delete_loan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Item::factory()->create(['stock' => 4, 'condition' => 'baik']);
        $loan = Loan::factory()->create([
            'item_id' => $item->id,
            'quantity' => 2,
            'status' => 'dipinjam',
        ]);

        $this->actingAs($admin)
            ->delete(route('loans.destroy', $loan))
            ->assertRedirect(route('loans.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('loans', ['id' => $loan->id]);
        $this->assertEquals(6, $item->fresh()->stock);
    }
}

