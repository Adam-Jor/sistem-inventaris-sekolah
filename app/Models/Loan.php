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
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'loan_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
