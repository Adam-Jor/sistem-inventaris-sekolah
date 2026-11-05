<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'item_id',
        'quantity',
        'loan_date',
        'due_date',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'loan_date' => 'date',
            'due_date' => 'date',
            'approved_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Admin yang memverifikasi (menyetujui/menolak) pengajuan ini.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function item(): BelongsTo
    {
        // withTrashed: riwayat tetap menampilkan barang yang sudah di-soft-delete.
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function returnLog(): HasOne
    {
        return $this->hasOne(ReturnLog::class, 'loan_id');
    }

    /**
     * Scope untuk transaksi peminjaman yang menunggu persetujuan
     */
    public function scopeWaiting(Builder $query): Builder
    {
        return $query->where('status', 'menunggu');
    }

    /**
     * Alias untuk scopeWaiting
     */
    public function scopePending(Builder $query): Builder
    {
        return $this->scopeWaiting($query);
    }

    /**
     * Scope untuk transaksi peminjaman aktif / disetujui 
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'dipinjam');
    }

    /**
     * Scope untuk transaksi peminjaman yang terlambat (overdue) 
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', 'terlambat')
              ->orWhere(function (Builder $sub) {
                  $sub->where('status', 'dipinjam')
                      ->where('due_date', '<', now()->toDateString());
              });
        });
    }

    /**
     * Scope untuk transaksi peminjaman yang sudah dikembalikan 
     */
    public function scopeReturned(Builder $query): Builder
    {
        return $query->where('status', 'dikembalikan');
    }

    /**
     * Scope untuk transaksi peminjaman yang ditolak
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'ditolak');
    }

    /**
     * Scope untuk peminjaman aktif yang mendekati jatuh tempo (hari ini s/d H-N).
     */
    public function scopeApproachingDue(Builder $query, int $daysBefore = 1): Builder
    {
        $today = now()->toDateString();
        $limitDate = now()->addDays(max(0, $daysBefore))->toDateString();

        return $query->where('status', 'dipinjam')
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $limitDate);
    }

    /**
     * Cek apakah pinjaman ini sedang mendekati batas waktu pengembalian (hari ini s/d H-N).
     */
    public function isApproachingDue(int $daysBefore = 1): bool
    {
        if ($this->status !== 'dipinjam' || ! $this->due_date) {
            return false;
        }

        $today = now()->startOfDay();
        $limit = now()->addDays(max(0, $daysBefore))->endOfDay();

        return $this->due_date->gte($today) && $this->due_date->lte($limit);
    }

    /**
     * Cek apakah pinjaman ini sudah melewati batas waktu pengembalian
     */
    public function isOverdue(): bool
    {
        return $this->status === 'terlambat' ||
               ($this->status === 'dipinjam' && $this->due_date < now()->startOfDay());
    }

    /**
     * Tandai otomatis peminjaman berstatus 'dipinjam' yang sudah melewati due_date menjadi 'terlambat' (LOA-05).
     */
    public static function syncOverdueStatuses(): int
    {
        return static::query()
            ->where('status', 'dipinjam')
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => 'terlambat']);
    }

    /**
     * Tren jumlah pengajuan & peminjaman disetujui selama 6 bulan terakhir.
     */
    public static function monthlyTrend(): \Illuminate\Support\Collection
    {
        $start = now()->startOfMonth()->subMonths(5);
        $approvedStatuses = ['dipinjam', 'terlambat', 'dikembalikan'];

        $loans = static::query()
            ->whereDate('loan_date', '>=', $start->toDateString())
            ->get(['loan_date', 'status']);

        return collect(range(0, 5))->map(function (int $offset) use ($start, $loans, $approvedStatuses) {
            $month = $start->copy()->addMonths($offset);
            $monthLoans = $loans->filter(fn (self $loan) => $loan->loan_date?->format('Y-m') === $month->format('Y-m'));

            return [
                'label' => $month->translatedFormat('M'),
                'full_label' => $month->translatedFormat('F Y'),
                'requests' => $monthLoans->count(),
                'approved' => $monthLoans->whereIn('status', $approvedStatuses)->count(),
            ];
        });
    }
}
