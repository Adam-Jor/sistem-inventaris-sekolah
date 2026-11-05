<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\LoanDueReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoanNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sends_approaching_and_overdue_email_notifications_without_duplicates(): void
    {
        Notification::fake();

        $userApproaching = User::factory()->create(['role' => 'user', 'name' => 'Siswa Besok Jatuh Tempo']);
        $userOverdue = User::factory()->create(['role' => 'user', 'name' => 'Siswa Terlambat']);
        $userSafe = User::factory()->create(['role' => 'user', 'name' => 'Siswa Masih Lama']);
        $item = Item::factory()->create(['name' => 'Proyektor Epson EB-X500', 'code' => 'ELK-001', 'stock' => 5]);

        // 1. Mendekati jatuh tempo (besok / H-1)
        $approachingLoan = Loan::factory()->for($userApproaching)->for($item)->create([
            'status' => 'dipinjam',
            'loan_date' => now()->subDays(2)->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
        ]);

        // 2. Melewati jatuh tempo (2 hari lalu, masih berstatus dipinjam -> otomatis disinkronkan ke terlambat)
        $overdueLoan = Loan::factory()->for($userOverdue)->for($item)->create([
            'status' => 'dipinjam',
            'loan_date' => now()->subDays(5)->toDateString(),
            'due_date' => now()->subDays(2)->toDateString(),
        ]);

        // 3. Masih lama (5 hari lagi) -> tidak dikirimi email
        Loan::factory()->for($userSafe)->for($item)->create([
            'status' => 'dipinjam',
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->artisan('loans:send-due-reminders')
            ->expectsOutputToContain('2 terkirim (1 mendekati jatuh tempo, 1 melewati jatuh tempo)')
            ->assertExitCode(0);

        $this->assertSame('terlambat', $overdueLoan->fresh()->status);
        $this->assertNotNull($approachingLoan->fresh()->reminder_sent_at);
        $this->assertNotNull($overdueLoan->fresh()->reminder_sent_at);

        Notification::assertSentTo(
            $userApproaching,
            LoanDueReminderNotification::class,
            function (LoanDueReminderNotification $notification) use ($userApproaching, $approachingLoan) {
                $mail = $notification->toMail($userApproaching);

                return $notification->loan->is($approachingLoan)
                    && $notification->type === 'approaching'
                    && str_contains($mail->subject, '[Pengingat Jatuh Tempo]')
                    && str_contains($mail->subject, 'Proyektor Epson EB-X500');
            }
        );

        Notification::assertSentTo(
            $userOverdue,
            LoanDueReminderNotification::class,
            function (LoanDueReminderNotification $notification) use ($userOverdue, $overdueLoan) {
                $mail = $notification->toMail($userOverdue);

                return $notification->loan->is($overdueLoan)
                    && $notification->type === 'overdue'
                    && str_contains($mail->subject, '[Peringatan Terlambat]')
                    && str_contains($mail->subject, 'Proyektor Epson EB-X500');
            }
        );

        Notification::assertNotSentTo($userSafe, LoanDueReminderNotification::class);

        // Eksekusi ulang di hari yang sama tidak mengirim email ganda
        Notification::fake();

        $this->artisan('loans:send-due-reminders')
            ->expectsOutputToContain('0 terkirim')
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_admin_can_trigger_single_and_bulk_email_notifications_and_non_admin_is_forbidden(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $borrower = User::factory()->create(['role' => 'user', 'name' => 'Rina Peminjam']);
        $item = Item::factory()->create(['name' => 'Mikroskop Binokuler', 'stock' => 3]);

        $loan = Loan::factory()->for($borrower)->for($item)->create([
            'status' => 'dipinjam',
            'loan_date' => now()->subDay()->toDateString(),
            'due_date' => now()->toDateString(),
        ]);

        // Non-admin ditolak (403)
        $this->actingAs($borrower)
            ->post(route('loans.notify', $loan))
            ->assertForbidden();

        $this->actingAs($borrower)
            ->post(route('loans.notify-due'))
            ->assertForbidden();

        // Admin mengirim notifikasi massal
        $this->actingAs($admin)
            ->from(route('loans.index'))
            ->post(route('loans.notify-due'))
            ->assertRedirect(route('loans.index'))
            ->assertSessionHas('success');

        Notification::assertSentTo($borrower, LoanDueReminderNotification::class);
        $this->assertNotNull($loan->fresh()->reminder_sent_at);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'notify_loan',
        ]);

        // Admin juga dapat mengirim ulang manual dari halaman detail peminjaman
        Notification::fake();

        $this->actingAs($admin)
            ->from(route('loans.show', $loan))
            ->post(route('loans.notify', $loan))
            ->assertRedirect(route('loans.show', $loan))
            ->assertSessionHas('success');

        Notification::assertSentTo($borrower, LoanDueReminderNotification::class);
    }

    public function test_smtp_failure_is_handled_gracefully_and_routes_are_throttled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $borrower = User::factory()->create(['role' => 'user', 'email' => 'siswa@gmail.com']);
        $item = Item::factory()->create(['name' => 'Gelas Ukur', 'stock' => 5]);

        $loan = Loan::factory()->for($borrower)->for($item)->create([
            'status' => 'dipinjam',
            'loan_date' => now()->subDay()->toDateString(),
            'due_date' => now()->toDateString(),
        ]);

        // Simulasikan kegagalan koneksi SMTP saat pengiriman notifikasi
        Notification::shouldReceive('send')
            ->andThrow(new \RuntimeException('SMTP connection refused'));

        $this->actingAs($admin)
            ->from(route('loans.show', $loan))
            ->post(route('loans.notify', $loan))
            ->assertRedirect(route('loans.show', $loan))
            ->assertSessionHas('error');

        $this->assertNull($loan->fresh()->reminder_sent_at);

        // Pastikan middleware throttle:10,1 terpasang pada kedua route notifikasi
        $notifyRoute = app('router')->getRoutes()->getByName('loans.notify');
        $bulkRoute = app('router')->getRoutes()->getByName('loans.notify-due');

        $this->assertContains('throttle:10,1', $notifyRoute->gatherMiddleware());
        $this->assertContains('throttle:10,1', $bulkRoute->gatherMiddleware());
    }
}

