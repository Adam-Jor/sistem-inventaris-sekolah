<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReturnLogRequest;
use App\Models\Item;
use App\Models\Loan;
use App\Models\ReturnLog;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReturnLogController extends Controller
{
    /**
     * Tampilkan daftar catatan pengembalian beserta peminjaman aktif yang menunggu dikembalikan.
     */
    public function index(Request $request): View
    {
        Loan::syncOverdueStatuses();

        $search = $request->string('search')->trim()->toString();
        $condition = $request->string('condition')->trim()->toString();
        $perPage = $this->resolvePerPage($request);

        $returnLogs = ReturnLog::query()
            ->with(['loan.user', 'loan.item.category'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->whereHas('loan.item', function ($itemQuery) use ($search) {
                        $itemQuery->withTrashed()
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })->orWhereHas('loan.user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            })
            ->when($condition !== '', function ($query) use ($condition) {
                $query->where('condition_on_return', $condition);
            })
            ->latest('returned_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $unreturnedLoans = Loan::query()
            ->with(['user', 'item'])
            ->whereIn('status', ['dipinjam', 'terlambat'])
            ->orderBy('due_date')
            ->get();

        return view('return-logs.index', compact('returnLogs', 'unreturnedLoans', 'search', 'condition', 'perPage'));
    }

    /**
     * Tampilkan form pencatatan pengembalian untuk satu peminjaman (RET-01 & RET-02).
     */
    public function create(Loan $loan): View|RedirectResponse
    {
        Loan::syncOverdueStatuses();
        $loan->refresh();

        if ($loan->returnLog()->withTrashed()->exists() || $loan->status === 'dikembalikan') {
            return redirect()
                ->route('loans.show', $loan)
                ->with('error', 'Peminjaman ini sudah memiliki catatan pengembalian.');
        }

        if (! in_array($loan->status, ['dipinjam', 'terlambat'], true)) {
            return redirect()
                ->route('loans.show', $loan)
                ->with('error', 'Hanya peminjaman berstatus dipinjam atau terlambat yang dapat dicatat pengembaliannya.');
        }

        $loan->load(['user', 'item.category']);

        return view('return-logs.create', compact('loan'));
    }

    /**
     * Simpan catatan pengembalian, tambah stok kembali, dan perbarui kondisi barang jika rusak (RET-01 s/d RET-04).
     */
    public function store(StoreReturnLogRequest $request, Loan $loan): RedirectResponse
    {
        if ($loan->returnLog()->withTrashed()->exists() || $loan->status === 'dikembalikan') {
            return redirect()
                ->route('loans.show', $loan)
                ->with('error', 'Peminjaman ini sudah memiliki catatan pengembalian.');
        }

        if (! in_array($loan->status, ['dipinjam', 'terlambat'], true)) {
            return redirect()
                ->route('loans.show', $loan)
                ->with('error', 'Hanya peminjaman berstatus dipinjam atau terlambat yang dapat dicatat pengembaliannya.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $loan) {
            ReturnLog::create([
                'loan_id' => $loan->id,
                'returned_at' => $validated['returned_at'],
                'condition_on_return' => $validated['condition_on_return'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $loan->update(['status' => 'dikembalikan']);

            $item = Item::withTrashed()->lockForUpdate()->find($loan->item_id);

            if ($item) {
                $item->increment('stock', $loan->quantity);

                // RET-04: Perbarui kondisi barang di tabel items jika dikembalikan dalam kondisi rusak
                if (in_array($validated['condition_on_return'], ['rusak ringan', 'rusak berat'], true)) {
                    $item->update(['condition' => $validated['condition_on_return']]);
                }
            }

            $loan->load(['user', 'item']);
            $itemName = $loan->item?->name ?? 'Barang';
            $userName = $loan->user?->name ?? 'User';

            ActivityLogger::log(
                'return_loan',
                "Mencatat pengembalian {$itemName} oleh {$userName}, kondisi {$validated['condition_on_return']}."
            );

            if (in_array($validated['condition_on_return'], ['rusak ringan', 'rusak berat'], true)) {
                ActivityLogger::log(
                    'update_item',
                    "Mengubah kondisi {$itemName} menjadi {$validated['condition_on_return']}."
                );
            }
        });

        $loan->load(['item', 'user']);

        return redirect()
            ->route('return-logs.index')
            ->with('success', "Pengembalian \"{$loan->item?->name}\" oleh {$loan->user?->name} berhasil dicatat dan stok telah dikembalikan.");
    }

    /**
     * Tampilkan detail catatan pengembalian.
     */
    public function show(ReturnLog $returnLog): View
    {
        $returnLog->load(['loan.user', 'loan.item.category']);

        return view('return-logs.show', compact('returnLog'));
    }
}
