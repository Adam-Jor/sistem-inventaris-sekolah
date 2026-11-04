<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanDueReminderNotification extends Notification
{
    use Queueable;

    public readonly string $type;

    /**
     * Buat instance notifikasi jatuh tempo / keterlambatan peminjaman.
     *
     * @param  string|null  $type  'approaching' (mendekati jatuh tempo) atau 'overdue' (melewati jatuh tempo)
     */
    public function __construct(
        public readonly Loan $loan,
        ?string $type = null,
    ) {
        $this->loan->loadMissing(['item', 'user']);
        $this->type = $type ?? ($this->loan->isOverdue() ? 'overdue' : 'approaching');
    }

    /**
     * Saluran pengiriman notifikasi.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Representasi email notifikasi.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $itemName = $this->loan->item?->name ?? 'Barang Inventaris';
        $itemCode = $this->loan->item?->code ?? '-';
        $quantity = number_format((int) $this->loan->quantity, 0, ',', '.');
        $loanDate = $this->loan->loan_date?->translatedFormat('d M Y') ?? '-';
        $dueDate = $this->loan->due_date?->translatedFormat('d M Y') ?? '-';
        $today = now()->startOfDay();
        $dueDay = $this->loan->due_date?->copy()->startOfDay() ?? $today;

        if ($this->type === 'overdue') {
            $daysOverdue = max(1, (int) $dueDay->diffInDays($today));

            return (new MailMessage)
                ->error()
                ->subject("[Peringatan Terlambat] Pengembalian Barang: {$itemName}")
                ->greeting("Halo {$notifiable->name},")
                ->line("Masa peminjaman barang inventaris sekolah Anda telah melewati batas waktu pengembalian (**terlambat {$daysOverdue} hari**).")
                ->line("**Barang:** {$itemName} ({$itemCode})")
                ->line("**Jumlah Dipinjam:** {$quantity} unit")
                ->line("**Tanggal Pinjam:** {$loanDate}")
                ->line("**Batas Tanggal Kembali:** {$dueDate}")
                ->action('Lihat Detail Peminjaman', route('loans.show', $this->loan))
                ->line('Mohon segera mengembalikan barang tersebut kepada petugas inventaris sekolah.')
                ->salutation('Salam, Tim Inventaris Sekolah');
        }

        $daysRemaining = max(0, (int) $today->diffInDays($dueDay, false));
        $timeNotice = $daysRemaining === 0
            ? "jatuh pada **hari ini ({$dueDate})**"
            : "akan jatuh tempo dalam **{$daysRemaining} hari ({$dueDate})**";

        return (new MailMessage)
            ->subject("[Pengingat Jatuh Tempo] Pengembalian Barang: {$itemName}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Kami mengingatkan bahwa batas waktu pengembalian barang inventaris sekolah yang Anda pinjam {$timeNotice}.")
            ->line("**Barang:** {$itemName} ({$itemCode})")
            ->line("**Jumlah Dipinjam:** {$quantity} unit")
            ->line("**Tanggal Pinjam:** {$loanDate}")
            ->line("**Batas Tanggal Kembali:** {$dueDate}")
            ->action('Lihat Detail Peminjaman', route('loans.show', $this->loan))
            ->line('Pastikan barang dikembalikan tepat waktu dalam kondisi baik kepada petugas inventaris sekolah.')
            ->salutation('Salam, Tim Inventaris Sekolah');
    }

    /**
     * Representasi array notifikasi.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'loan_id' => $this->loan->id,
            'item_id' => $this->loan->item_id,
            'item_name' => $this->loan->item?->name,
            'quantity' => $this->loan->quantity,
            'due_date' => $this->loan->due_date?->toDateString(),
            'type' => $this->type,
        ];
    }
}

