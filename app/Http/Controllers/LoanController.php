<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanRequest;
use App\Models\Item;
use App\Models\Loan;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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
        $loan->refresh()->load(['user', 'item.category', 'returnLog']);

        return view('loans.show', compact('loan'));
    }

    /**
     * Admin menyetujui pengajuan peminjaman dan mengurangi stok barang (LOA-03 & LOA-04).
     */
    public function approve(Loan $loan): RedirectResponse
    {
        if ($loan->status !== 'menunggu') {
            return back()->with('error', 'Hanya pengajuan berstatus menunggu yang dapat disetujui.');
        }

        return DB::transaction(function () use ($loan) {
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
            $loan->update(['status' => $newStatus]);

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
}
