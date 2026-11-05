<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanRequest;
use App\Models\Item;
use App\Models\Loan;
use App\Notifications\LoanDueReminderNotification;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoanController extends Controller
{
    /**
     * Tampilkan daftar peminjaman (Admin melihat semua, User hanya melihat miliknya).
     */
    public function index(Request $request): View
    {
        Loan::syncOverdueStatuses();

        $user = $request->user();
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $perPage = $this->resolvePerPage($request);

        $loans = Loan::query()
            ->with(['user', 'item.category', 'returnLog'])
            ->when(! $user->isAdmin(), function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->when($search !== '', function ($query) use ($search, $user) {
                $query->where(function ($sub) use ($search, $user) {
                    $sub->whereHas('item', function ($itemQuery) use ($search) {
                        $itemQuery->withTrashed()
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });

                    if ($user->isAdmin()) {
                        $sub->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                    }
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('loans.index', compact('loans', 'search', 'status', 'perPage'));
    }

    /**
     * Tampilkan form pengajuan peminjaman baru.
     */
    public function create(Request $request): View
    {
        $items = Item::available()
            ->with('category')
            ->orderBy('name')
            ->get();

        $selectedItemId = $request->input('item_id', '');

        return view('loans.create', compact('items', 'selectedItemId'));
    }

    /**
     * Simpan pengajuan peminjaman baru (status awal: menunggu).
     */
    public function store(StoreLoanRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $loan = Loan::create([
            'user_id' => $user->id,
            'item_id' => $validated['item_id'],
            'quantity' => $validated['quantity'],
            'loan_date' => $validated['loan_date'],
            'due_date' => $validated['due_date'],
            'status' => 'menunggu',
            'notes' => $validated['notes'] ?? null,
        ]);

        $loan->load('item');

        ActivityLogger::log(
            'request_loan',
            "{$user->name} mengajukan peminjaman {$loan->item->name} ({$loan->quantity} unit)."
        );

        return redirect()
            ->route('loans.index')
            ->with('success', "Pengajuan peminjaman \"{$loan->item->name}\" berhasil dikirim dan menunggu verifikasi Admin.");
    }

    /**
     * Tampilkan detail peminjaman (User hanya boleh mengakses miliknya sendiri).
     */
    public function show(Request $request, Loan $loan): View
    {
        abort_unless(
            $request->user()->isAdmin() || $loan->user_id === $request->user()->id,
            403,
            'Anda tidak memiliki akses ke data peminjaman ini.'
        );

        Loan::syncOverdueStatuses();
        $loan->refresh()->load(['user', 'item.category', 'returnLog', 'approver']);

        return view('loans.show', compact('loan'));
    }

    /**
     * Admin menyetujui pengajuan peminjaman dan mengurangi stok barang (LOA-03 & LOA-04).
     */
    public function approve(Request $request, Loan $loan): RedirectResponse
    {
        if ($loan->status !== 'menunggu') {
            return back()->with('error', 'Hanya pengajuan berstatus menunggu yang dapat disetujui.');
        }

        return DB::transaction(function () use ($request, $loan) {
            $item = Item::withTrashed()->lockForUpdate()->find($loan->item_id);

            if (! $item || $item->trashed()) {
                return back()->with('error', 'Barang tidak ditemukan atau sudah dihapus.');
            }

            if ($item->stock < $loan->quantity) {
                return back()->with(
                    'error',
                    "Stok barang \"{$item->name}\" tidak mencukupi (tersedia: {$item->stock} unit, diminta: {$loan->quantity} unit)."
                );
            }

            $item->decrement('stock', $loan->quantity);

            $newStatus = $loan->due_date->lt(now()->startOfDay()) ? 'terlambat' : 'dipinjam';
            $loan->update([
                'status' => $newStatus,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            $loan->load('user');

            ActivityLogger::log(
                'verify_loan',
                "Menyetujui peminjaman {$item->name} ({$loan->quantity} unit) oleh {$loan->user->name}."
            );

            return back()->with('success', "Peminjaman \"{$item->name}\" oleh {$loan->user->name} telah disetujui.");
        });
    }

    /**
     * Admin menolak pengajuan peminjaman (LOA-03).
     */
    public function reject(Request $request, Loan $loan): RedirectResponse
    {
        if ($loan->status !== 'menunggu') {
            return back()->with('error', 'Hanya pengajuan berstatus menunggu yang dapat ditolak.');
        }

        $reason = $request->string('rejection_reason')->trim()->toString();
        $notes = $loan->notes;

        if ($reason !== '') {
            $notes = $notes ? "{$notes}\n(Ditolak: {$reason})" : "(Ditolak: {$reason})";
        }

        $loan->update([
            'status' => 'ditolak',
            'notes' => $notes,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $loan->load(['item', 'user']);

        ActivityLogger::log(
            'reject_loan',
            "Menolak peminjaman {$loan->item->name} oleh {$loan->user->name}".($reason !== '' ? ": {$reason}" : '.')
        );

        return back()->with('success', "Pengajuan peminjaman \"{$loan->item->name}\" oleh {$loan->user->name} telah ditolak.");
    }

    /**
     * Soft delete data peminjaman (LOA-07).
     * Jika peminjaman masih aktif (dipinjam/terlambat), kembalikan stok barang terlebih dahulu.
     */
    public function destroy(Loan $loan): RedirectResponse
    {
        DB::transaction(function () use ($loan) {
            $loan->load(['item', 'user']);

            if (in_array($loan->status, ['dipinjam', 'terlambat'], true) && $loan->item) {
                $loan->item->increment('stock', $loan->quantity);
            }

            $itemName = $loan->item?->name ?? 'Barang';
            $userName = $loan->user?->name ?? 'User';

            $loan->delete();

            ActivityLogger::log(
                'delete_loan',
                "Menghapus data peminjaman {$itemName} oleh {$userName}."
            );
        });

        return redirect()
            ->route('loans.index')
            ->with('success', 'Data peminjaman berhasil dihapus.');
    }

    /**
     * Admin mengirim email pengingat jatuh tempo / keterlambatan untuk satu peminjaman aktif.
     */
    public function sendNotification(Loan $loan): RedirectResponse
    {
        Loan::syncOverdueStatuses();
        $loan->refresh()->load(['user', 'item']);

        if (! in_array($loan->status, ['dipinjam', 'terlambat'], true)) {
            return back()->with('error', 'Pengingat email hanya dapat dikirim untuk peminjaman berstatus dipinjam atau terlambat.');
        }

        if (! $loan->user || ! $loan->user->email) {
            return back()->with('error', 'Peminjam tidak memiliki alamat email yang valid.');
        }

        if (! $loan->user->hasDeliverableEmail()) {
            return back()->with(
                'error',
                "Alamat email peminjam ({$loan->user->email}) menggunakan domain dummy (.test) sehingga tidak dapat dikirim ke internet. Gunakan akun peminjam dengan email asli."
            );
        }

        $type = $loan->isOverdue() ? 'overdue' : 'approaching';
        $itemName = $loan->item?->name ?? 'Barang';
        $label = $type === 'overdue' ? 'peringatan keterlambatan' : 'pengingat jatuh tempo';

        try {
            $loan->user->notify(new LoanDueReminderNotification($loan, $type));
        } catch (\Throwable $e) {
            report($e);

            return back()->with(
                'error',
                "Gagal mengirim email {$label} ke {$loan->user->email}. Pastikan koneksi internet dan konfigurasi SMTP sudah benar."
            );
        }

        $loan->forceFill(['reminder_sent_at' => now()])->save();

        ActivityLogger::log(
            'notify_loan',
            "Mengirim email {$label} peminjaman {$itemName} kepada {$loan->user->name} ({$loan->user->email})."
        );

        return back()->with(
            'success',
            "Email {$label} untuk \"{$itemName}\" berhasil dikirim ke {$loan->user->name} ({$loan->user->email})."
        );
    }

    /**
     * Admin mengirim email pengingat massal untuk seluruh peminjaman yang mendekati (H-1/Hari-H) atau melewati jatuh tempo.
     */
    public function sendBulkDueNotifications(): RedirectResponse
    {
        Loan::syncOverdueStatuses();

        $today = now()->toDateString();
        $limitDate = now()->addDay()->toDateString();

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
            ->where(function (Builder $query) use ($today) {
                $query->whereNull('reminder_sent_at')
                    ->orWhereDate('reminder_sent_at', '<', $today);
            })
            ->get();

        $approachingCount = 0;
        $overdueCount = 0;
        $failedCount = 0;

        foreach ($loans as $loan) {
            if (! $loan->user || ! $loan->user->hasDeliverableEmail()) {
                continue;
            }

            $type = $loan->isOverdue() ? 'overdue' : 'approaching';

            try {
                $loan->user->notify(new LoanDueReminderNotification($loan, $type));
                $loan->forceFill(['reminder_sent_at' => now()])->save();
            } catch (\Throwable $e) {
                report($e);
                $failedCount++;

                continue;
            }

            if ($type === 'overdue') {
                $overdueCount++;
            } else {
                $approachingCount++;
            }
        }

        $totalSent = $approachingCount + $overdueCount;

        if ($totalSent === 0 && $failedCount > 0) {
            return back()->with(
                'error',
                "Gagal mengirim {$failedCount} email notifikasi. Pastikan koneksi internet dan konfigurasi SMTP sudah benar."
            );
        }

        if ($totalSent === 0) {
            return back()->with(
                'success',
                'Tidak ada peminjaman mendekati/melewati jatuh tempo yang belum dikirimi email pengingat hari ini.'
            );
        }

        ActivityLogger::log(
            'notify_loan',
            "Mengirim {$totalSent} email pengingat jatuh tempo ({$approachingCount} mendekati jatuh tempo, {$overdueCount} terlambat)."
        );

        $suffix = $failedCount > 0 ? " ({$failedCount} gagal dikirim karena kendala SMTP)." : '.';

        return back()->with(
            'success',
            "Berhasil mengirim {$totalSent} email notifikasi ({$approachingCount} mendekati jatuh tempo, {$overdueCount} melewati jatuh tempo){$suffix}"
        );
    }

    /**
     * Ekspor data peminjaman ke format Excel (CSV dengan UTF-8 BOM) sesuai filter pencarian/status.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        Loan::syncOverdueStatuses();

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $loans = $this->buildAdminExportQuery($search, $status)->get();

        ActivityLogger::log(
            'export_loan',
            "Mengekspor {$loans->count()} data peminjaman ke file Excel (CSV)."
        );

        $filename = 'laporan-peminjaman-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($loans) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM agar Microsoft Excel langsung mengenali karakter dan kolom dengan benar
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'No',
                'Peminjam',
                'Email Peminjam',
                'Kode Barang',
                'Nama Barang',
                'Kategori',
                'Jumlah (Unit)',
                'Tanggal Pinjam',
                'Jatuh Tempo',
                'Status',
                'Diverifikasi Oleh',
                'Tanggal Dikembalikan',
                'Kondisi Saat Kembali',
                'Catatan',
            ]);

            foreach ($loans as $index => $loan) {
                fputcsv($handle, [
                    $index + 1,
                    $loan->user?->name ?? '-',
                    $loan->user?->email ?? '-',
                    $loan->item?->code ?? '-',
                    $loan->item?->name ?? '-',
                    $loan->item?->category?->name ?? '-',
                    $loan->quantity,
                    $loan->loan_date?->format('Y-m-d') ?? '-',
                    $loan->due_date?->format('Y-m-d') ?? '-',
                    ucfirst($loan->status),
                    $loan->approver?->name ?? '-',
                    $loan->returnLog?->returned_at?->format('Y-m-d') ?? '-',
                    $loan->returnLog ? ucwords($loan->returnLog->condition_on_return) : '-',
                    $loan->notes ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Tampilkan halaman cetak / simpan PDF untuk laporan peminjaman sesuai filter pencarian/status.
     */
    public function exportPdf(Request $request): View
    {
        Loan::syncOverdueStatuses();

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $loans = $this->buildAdminExportQuery($search, $status)->get();

        ActivityLogger::log(
            'export_loan',
            "Membuka laporan cetak/PDF untuk {$loans->count()} data peminjaman."
        );

        return view('loans.print', [
            'loans' => $loans,
            'search' => $search,
            'status' => $status,
            'printedAt' => now(),
            'printedBy' => $request->user(),
        ]);
    }

    /**
     * Query builder laporan peminjaman Admin dengan dukungan filter pencarian dan status.
     */
    private function buildAdminExportQuery(string $search, string $status): Builder
    {
        return Loan::query()
            ->with(['user', 'item.category', 'returnLog', 'approver'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $sub) use ($search) {
                    $sub->whereHas('item', function (Builder $itemQuery) use ($search) {
                        $itemQuery->withTrashed()
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            })
            ->when($status !== '', function (Builder $query) use ($status) {
                $query->where('status', $status);
            })
            ->latest();
    }
}
