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

        $period = (string) $request->query('periode', 'all');

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
            'monthlyTrend' => $this->monthlyTrend(),
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
            fn (Builder $query) => $query->whereDate('loans.loan_date', '>=', $startDate->toDateString())
        );
    }

    /**
     * Ringkasan angka utama (KPI).
     */
    private function summary(?Carbon $startDate): array
    {
        $totalRequests = $this->loansInPeriod($startDate)->count();

        $approvedQuery = $this->loansInPeriod($startDate)->whereIn('loans.status', self::APPROVED_STATUSES);
        $approvedCount = (clone $approvedQuery)->count();
        $unitsBorrowed = (int) (clone $approvedQuery)->sum('loans.quantity');
        $activeBorrowers = (clone $approvedQuery)->distinct()->count('loans.user_id');

        $rejectedCount = $this->loansInPeriod($startDate)->where('loans.status', 'ditolak')->count();
        $decidedCount = $approvedCount + $rejectedCount;

        // Peminjaman yang sudah dikembalikan beserta tanggal kembalinya.
        $returnedLoans = $this->loansInPeriod($startDate)
            ->join('return_logs', function ($join) {
                $join->on('return_logs.loan_id', '=', 'loans.id')
                    ->whereNull('return_logs.deleted_at');
            })
            ->where('loans.status', 'dikembalikan')
            ->get(['loans.loan_date', 'loans.due_date', 'return_logs.returned_at']);

        $onTimeCount = $returnedLoans->filter(
            fn ($loan) => Carbon::parse($loan->returned_at)->startOfDay()->lte(Carbon::parse($loan->due_date)->startOfDay())
        )->count();

        $averageDuration = $returnedLoans->isNotEmpty()
            ? round($returnedLoans->avg(
                fn ($loan) => max(0, Carbon::parse($loan->loan_date)->startOfDay()->diffInDays(Carbon::parse($loan->returned_at)->startOfDay()))
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
            ->select('loans.status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('loans.status')
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
                'count' => $count,
                'percentage' => round($count / $total * 100, 1),
            ];
        });
    }

    /**
     * Tren jumlah pengajuan & peminjaman disetujui selama 6 bulan terakhir.
     */
    private function monthlyTrend(): Collection
    {
        $start = now()->startOfMonth()->subMonths(5);

        $loans = Loan::query()
            ->whereDate('loan_date', '>=', $start->toDateString())
            ->get(['loan_date', 'status']);

        return collect(range(0, 5))->map(function (int $offset) use ($start, $loans) {
            $month = $start->copy()->addMonths($offset);
            $monthLoans = $loans->filter(fn (Loan $loan) => $loan->loan_date?->format('Y-m') === $month->format('Y-m'));

            return [
                'label' => $month->translatedFormat('M'),
                'full_label' => $month->translatedFormat('F Y'),
                'requests' => $monthLoans->count(),
                'approved' => $monthLoans->whereIn('status', self::APPROVED_STATUSES)->count(),
            ];
        });
    }

    /**
     * Barang yang paling sering dipinjam.
     */
    private function topItems(?Carbon $startDate): Collection
    {
        return $this->loansInPeriod($startDate)
            ->whereIn('loans.status', self::APPROVED_STATUSES)
            ->select('loans.item_id')
            ->selectRaw('COUNT(*) as loans_count, SUM(loans.quantity) as units_total')
            ->groupBy('loans.item_id')
            ->orderByDesc('loans_count')
            ->orderByDesc('units_total')
            ->with('item.category')
            ->take(5)
            ->get();
    }

    /**
     * Query agregat per peminjam (jumlah transaksi, unit, dan keterlambatan).
     */
    private function borrowerAggregate(?Carbon $startDate): Builder
    {
        return $this->loansInPeriod($startDate)
            ->leftJoin('return_logs', function ($join) {
                $join->on('return_logs.loan_id', '=', 'loans.id')
                    ->whereNull('return_logs.deleted_at');
            })
            ->whereIn('loans.status', self::APPROVED_STATUSES)
            ->select('loans.user_id')
            ->selectRaw('COUNT(*) as loans_count')
            ->selectRaw('SUM(loans.quantity) as units_total')
            ->selectRaw(
                "SUM(CASE WHEN loans.status = 'terlambat'
                    OR (return_logs.id IS NOT NULL AND DATE(return_logs.returned_at) > DATE(loans.due_date))
                    THEN 1 ELSE 0 END) as late_count"
            )
            ->groupBy('loans.user_id')
            ->with('user');
    }

    /**
     * Pengguna paling aktif meminjam.
     */
    private function topBorrowers(?Carbon $startDate): Collection
    {
        return $this->borrowerAggregate($startDate)
            ->orderByDesc('loans_count')
            ->orderByDesc('units_total')
            ->take(5)
            ->get();
    }

    /**
     * Pengguna yang paling sering terlambat mengembalikan barang.
     */
    private function lateBorrowers(?Carbon $startDate): Collection
    {
        return $this->borrowerAggregate($startDate)
            ->orderByDesc('late_count')
            ->orderByDesc('loans_count')
            ->take(10)
            ->get()
            ->filter(fn ($row) => (int) $row->late_count > 0)
            ->take(5)
            ->values();
    }

    /**
     * Kategori barang yang paling diminati.
     */
    private function topCategories(?Carbon $startDate): Collection
    {
        return $this->loansInPeriod($startDate)
            ->join('items', 'items.id', '=', 'loans.item_id')
            ->join('categories', 'categories.id', '=', 'items.category_id')
            ->whereIn('loans.status', self::APPROVED_STATUSES)
            ->select('categories.id', 'categories.name')
            ->selectRaw('COUNT(*) as loans_count, SUM(loans.quantity) as units_total')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('loans_count')
            ->take(5)
            ->toBase()
            ->get();
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
