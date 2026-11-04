<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Notifications\LoanDueReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class SendLoanDueRemindersCommand extends Command
{
    /**
     * Signature perintah Artisan.
     *
     * @var string
     */
    protected $signature = 'loans:send-due-reminders
                            {--days=1 : Jumlah hari sebelum jatuh tempo untuk pengingat mendekati jatuh tempo (H-N)}
                            {--force : Kirim ulang email meskipun hari ini sudah pernah dikirim}';

    /**
     * Deskripsi perintah Artisan.
     *
     * @var string
     */
    protected $description = 'Sinkronkan status peminjaman terlambat dan kirim notifikasi email untuk peminjaman yang mendekati atau melewati jatuh tempo';

    /**
     * Jalankan perintah Artisan.
     */
    public function handle(): int
    {
        $daysBefore = max(0, (int) $this->option('days'));
        $force = (bool) $this->option('force');

        // Pastikan peminjaman yang sudah lewat jatuh tempo ditandai 'terlambat' terlebih dahulu.
        Loan::syncOverdueStatuses();

        $today = now()->toDateString();
        $limitDate = now()->addDays($daysBefore)->toDateString();

        $loans = Loan::query()
            ->with(['user', 'item'])
            ->where(function (Builder $query) use ($today, $limitDate) {
                $query->where('status', 'terlambat')
                    ->orWhere(function (Builder $sub) use ($today, $limitDate) {
                        $sub->where('status', 'dipinjam')
                            ->whereDate('due_date', '>=', $today)
                            ->whereDate('due_date', '<=', $limitDate);
                    });
            })
            ->when(! $force, function (Builder $query) use ($today) {
                $query->where(function (Builder $sub) use ($today) {
                    $sub->whereNull('reminder_sent_at')
                        ->orWhereDate('reminder_sent_at', '<', $today);
                });
            })
            ->get();

        $approachingCount = 0;
        $overdueCount = 0;

        foreach ($loans as $loan) {
            if (! $loan->user || ! $loan->user->hasDeliverableEmail()) {
                continue;
            }

            $type = $loan->isOverdue() ? 'overdue' : 'approaching';

            $loan->user->notify(new LoanDueReminderNotification($loan, $type));
            $loan->forceFill(['reminder_sent_at' => now()])->save();

            if ($type === 'overdue') {
                $overdueCount++;
            } else {
                $approachingCount++;
            }
        }

        $totalSent = $approachingCount + $overdueCount;

        $this->info(
            "Notifikasi email selesai diproses: {$totalSent} terkirim ({$approachingCount} mendekati jatuh tempo, {$overdueCount} melewati jatuh tempo)."
        );

        return self::SUCCESS;
    }
}

