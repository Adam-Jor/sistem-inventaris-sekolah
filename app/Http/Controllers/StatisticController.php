<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StatisticController extends Controller
{
    /**
     * Pilihan periode filter statistik (berdasarkan tanggal pinjam).
     */
    public const PERIODS = [
        '30' => '30 Hari',
        '90' => '3 Bulan',
        '365' => '12 Bulan',
        'all' => 'Semua Waktu',
    ];

    /**
     * Status peminjaman yang dianggap sudah disetujui (barang benar-benar keluar).
     */
    private const APPROVED_STATUSES = ['dipinjam', 'terlambat', 'dikembalikan'];

    /**
     * Batas stok yang dianggap menipis.
     */
    private const LOW_STOCK_THRESHOLD = 2;

    /**
     * Tampilkan dashboard statistik inventaris & peminjaman (khusus Admin).
     */
    public function index(Request $request): View
    {
        Loan::syncOverdueStatuses();

        $period = (string) ($request->query('period') ?? $request->query('periode', 'all'));

        if (! array_key_exists($period, self::PERIODS)) {
            $period = 'all';
        }

        $startDate = $period === 'all'
            ? null
            : now()->subDays((int) $period - 1)->startOfDay();

        return view('statistics.index', [
            'period' => $period,
            'periods' => self::PERIODS,
            'startDate' => $startDate,
            'summary' => $this->summary($startDate),
            'statusDistribution' => $this->statusDistribution($startDate),
            'monthlyTrend' => Loan::monthlyTrend(),
            'topItems' => $this->topItems($startDate),
            'topBorrowers' => $this->topBorrowers($startDate),
            'lateBorrowers' => $this->lateBorrowers($startDate),
            'topCategories' => $this->topCategories($startDate),
            'conditionDistribution' => $this->conditionDistribution(),
            'lowStockItems' => $this->lowStockItems(),
            'idleItems' => $this->idleItems($startDate),
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
        ]);
    }

    /**
     * Query dasar peminjaman yang sudah difilter sesuai periode.
     */
    private function loansInPeriod(?Carbon $startDate): Builder
    {
        return Loan::query()->when(
            $startDate,
            fn (Builder $query) => $query->whereDate('loan_date', '>=', $startDate->toDateString())
        );
    }

    /**
     * Ringkasan angka utama (KPI).
     */
    private function summary(?Carbon $startDate): array
    {
        $totalRequests = $this->loansInPeriod($startDate)->count();

        $approvedQuery = $this->loansInPeriod($startDate)->whereIn('status', self::APPROVED_STATUSES);
        $approvedCount = (clone $approvedQuery)->count();
        $unitsBorrowed = (int) (clone $approvedQuery)->sum('quantity');
        $activeBorrowers = (clone $approvedQuery)->distinct()->count('user_id');

        $rejectedCount = $this->loansInPeriod($startDate)->where('status', 'ditolak')->count();
        $decidedCount = $approvedCount + $rejectedCount;

        // Peminjaman yang sudah dikembalikan beserta relasi catatan pengembaliannya.
        $returnedLoans = $this->loansInPeriod($startDate)
            ->with('returnLog')
            ->where('status', 'dikembalikan')
            ->whereHas('returnLog')
            ->get();

        $onTimeCount = $returnedLoans->filter(
            fn (Loan $loan) => $loan->returnLog?->returned_at !== null
                && $loan->due_date !== null
                && $loan->returnLog->returned_at->copy()->startOfDay()->lte($loan->due_date->copy()->startOfDay())
        )->count();

        $averageDuration = $returnedLoans->isNotEmpty()
            ? round($returnedLoans->avg(
                fn (Loan $loan) => max(0, $loan->loan_date->copy()->startOfDay()->diffInDays($loan->returnLog->returned_at->copy()->startOfDay()))
            ), 1)
            : null;

        return [
            'total_requests' => $totalRequests,
            'approved_count' => $approvedCount,
            'units_borrowed' => $unitsBorrowed,
            'active_borrowers' => $activeBorrowers,
            'approval_rate' => $decidedCount > 0 ? round($approvedCount / $decidedCount * 100, 1) : null,
            'returned_count' => $returnedLoans->count(),
            'on_time_count' => $onTimeCount,
            'on_time_rate' => $returnedLoans->isNotEmpty() ? round($onTimeCount / $returnedLoans->count() * 100, 1) : null,
            'average_duration' => $averageDuration,
        ];
    }

    /**
     * Distribusi jumlah peminjaman berdasarkan status.
     */
    private function statusDistribution(?Carbon $startDate): Collection
    {
        $counts = $this->loansInPeriod($startDate)
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = max(1, (int) $counts->sum());

        return collect([
            'menunggu' => ['label' => 'Menunggu', 'color' => 'warning'],
            'dipinjam' => ['label' => 'Dipinjam', 'color' => 'info'],
            'terlambat' => ['label' => 'Terlambat', 'color' => 'danger'],
            'dikembalikan' => ['label' => 'Dikembalikan', 'color' => 'success'],
            'ditolak' => ['label' => 'Ditolak', 'color' => 'secondary'],
        ])->map(function (array $meta, string $status) use ($counts, $total) {
            $count = (int) ($counts[$status] ?? 0);

            return $meta + [
                'key' => $status,
                'count' => $count,
                'percentage' => round($count / $total * 100, 1),
            ];
        });
    }

    /**
     * Barang yang paling sering dipinjam.
     */
    private function topItems(?Carbon $startDate): Collection
    {
        return $this->loansInPeriod($startDate)
            ->whereIn('status', self::APPROVED_STATUSES)
            ->select('item_id')
            ->selectRaw('COUNT(*) as loans_count, SUM(quantity) as units_total')
            ->groupBy('item_id')
            ->orderByDesc('loans_count')
            ->orderByDesc('units_total')
            ->with('item.category')
            ->take(5)
            ->get();
    }

    /**
     * Agregasi statistik per peminjam menggunakan Eloquent Relationship (tanpa manual JOIN).
     */
    private function borrowerStats(?Carbon $startDate): Collection
    {
        return $this->loansInPeriod($startDate)
            ->with(['user', 'returnLog'])
            ->whereIn('status', self::APPROVED_STATUSES)
            ->get()
            ->groupBy('user_id')
            ->map(function (Collection $loans, $userId) {
                $lateCount = $loans->filter(function (Loan $loan) {
                    if ($loan->status === 'terlambat') {
                        return true;
                    }

                    return $loan->returnLog?->returned_at !== null
                        && $loan->due_date !== null
                        && $loan->returnLog->returned_at->copy()->startOfDay()->gt($loan->due_date->copy()->startOfDay());
                })->count();

                return (object) [
                    'user_id' => (int) $userId,
                    'user' => $loans->first()?->user,
                    'loans_count' => $loans->count(),
                    'units_total' => (int) $loans->sum('quantity'),
                    'late_count' => $lateCount,
                ];
            })
            ->values();
    }

    /**
     * Pengguna paling aktif meminjam.
     */
    private function topBorrowers(?Carbon $startDate): Collection
    {
        return $this->borrowerStats($startDate)
            ->sortByDesc(fn ($row) => [$row->loans_count, $row->units_total])
            ->take(5)
            ->values();
    }

    /**
     * Pengguna yang paling sering terlambat mengembalikan barang.
     */
    private function lateBorrowers(?Carbon $startDate): Collection
    {
        return $this->borrowerStats($startDate)
            ->filter(fn ($row) => (int) $row->late_count > 0)
            ->sortByDesc(fn ($row) => [$row->late_count, $row->loans_count])
            ->take(5)
            ->values();
    }

    /**
     * Kategori barang yang paling diminati menggunakan Eloquent Relationship.
     */
    private function topCategories(?Carbon $startDate): Collection
    {
        return $this->loansInPeriod($startDate)
            ->with('item.category')
            ->whereIn('status', self::APPROVED_STATUSES)
            ->get()
            ->filter(fn (Loan $loan) => $loan->item?->category !== null)
            ->groupBy(fn (Loan $loan) => $loan->item->category->id)
            ->map(function (Collection $loans) {
                $category = $loans->first()->item->category;

                return (object) [
                    'id' => $category->id,
                    'name' => $category->name,
                    'loans_count' => $loans->count(),
                    'units_total' => (int) $loans->sum('quantity'),
                ];
            })
            ->sortByDesc(fn ($row) => [$row->loans_count, $row->units_total])
            ->take(5)
            ->values();
    }

    /**
     * Distribusi kondisi seluruh barang inventaris.
     */
    private function conditionDistribution(): Collection
    {
        $counts = Item::query()
            ->select('condition')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('condition')
            ->pluck('total', 'condition');

        $total = max(1, (int) $counts->sum());

        return collect([
            'baik' => ['label' => 'Baik', 'color' => 'success'],
            'rusak ringan' => ['label' => 'Rusak Ringan', 'color' => 'warning'],
            'rusak berat' => ['label' => 'Rusak Berat', 'color' => 'danger'],
        ])->map(function (array $meta, string $condition) use ($counts, $total) {
            $count = (int) ($counts[$condition] ?? 0);

            return $meta + [
                'key' => $condition,
                'count' => $count,
                'percentage' => round($count / $total * 100, 1),
            ];
        });
    }

    /**
     * Barang dengan stok menipis.
     */
    private function lowStockItems(): Collection
    {
        return Item::with('category')
            ->where('stock', '<=', self::LOW_STOCK_THRESHOLD)
            ->orderBy('stock')
            ->orderBy('name')
            ->take(5)
            ->get();
    }

    /**
     * Barang yang belum pernah dipinjam pada periode terpilih.
     */
    private function idleItems(?Carbon $startDate): array
    {
        $query = Item::with('category')->whereDoesntHave('loans', function (Builder $loanQuery) use ($startDate) {
            $loanQuery->whereIn('status', self::APPROVED_STATUSES)
                ->when($startDate, fn (Builder $q) => $q->whereDate('loan_date', '>=', $startDate->toDateString()));
        });

        return [
            'count' => (clone $query)->count(),
            'items' => $query->orderBy('name')->take(5)->get(),
        ];
    }
}
