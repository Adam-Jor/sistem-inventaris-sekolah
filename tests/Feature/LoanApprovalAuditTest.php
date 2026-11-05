<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanApprovalAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_and_rejecting_records_admin_and_time(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Penguji']);
        $borrower = User::factory()->create(['role' => 'user']);
        $item = Item::factory()->create(['stock' => 5]);

        $toApprove = Loan::factory()->for($borrower)->for($item)->create([
            'status' => 'menunggu',
            'quantity' => 1,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
        ]);
        $toReject = Loan::factory()->for($borrower)->for($item)->create([
            'status' => 'menunggu',
            'quantity' => 1,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->assertNull($toApprove->approved_by);

        $this->actingAs($admin)->patch(route('loans.approve', $toApprove))->assertSessionHas('success');
        $this->actingAs($admin)->patch(route('loans.reject', $toReject), ['rejection_reason' => 'Stok dipakai ujian'])->assertSessionHas('success');

        foreach ([$toApprove, $toReject] as $loan) {
            $loan->refresh();
            $this->assertSame($admin->id, $loan->approved_by);
            $this->assertNotNull($loan->approved_at);
            $this->assertTrue($loan->approver->is($admin));
        }

        $this->actingAs($admin)
            ->get(route('loans.show', $toApprove))
            ->assertOk()
            ->assertSee('Diverifikasi Oleh')
            ->assertSee('Admin Penguji');
    }

    public function test_pending_loan_shows_not_verified_yet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $loan = Loan::factory()->create(['status' => 'menunggu']);

        $this->actingAs($admin)
            ->get(route('loans.show', $loan))
            ->assertOk()
            ->assertSee('Belum diverifikasi');
    }

    public function test_deleting_the_verifying_admin_keeps_the_loan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $loan = Loan::factory()->create(['status' => 'dipinjam', 'approved_by' => $admin->id, 'approved_at' => now()]);

        $admin->delete();

        $this->assertNull($loan->fresh()->approved_by);
    }

    public function test_non_admin_cannot_verify_or_reach_admin_pages(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $loan = Loan::factory()->create(['status' => 'menunggu']);

        $this->actingAs($user);

        $this->patch(route('loans.approve', $loan))->assertForbidden();
        $this->patch(route('loans.reject', $loan))->assertForbidden();
        $this->delete(route('loans.destroy', $loan))->assertForbidden();
        $this->get(route('loans.return.create', $loan))->assertForbidden();
        $this->get(route('return-logs.index'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('activity-logs.index'))->assertForbidden();
        $this->get(route('statistics.index'))->assertForbidden();

        $this->assertNull($loan->fresh()->approved_by);
        $this->assertSame('menunggu', $loan->fresh()->status);
    }
}

