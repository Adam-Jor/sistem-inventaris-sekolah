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

        $availableYears = $this->availableYears();
        $availableMonths = $this->availableMonths();
        $trendOptions = $this->buildTrendOptions($availableYears);

        // 1. Cek apakah Admin memilih bulan spesifik (?month=YYYY-MM)
        $selectedMonth = $this->resolveSelectedMonth($request->query('month'));
        $selectedMonthLabel = null;
        $endDate = null;

        if ($selectedMonth !== null) {
            $startDate = Carbon::createFromFormat('Y-m-d', $selectedMonth.'-01')->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            $selectedMonthLabel = $startDate->translatedFormat('F Y');
            $period = 'month';
            $periodLabel = 'Bulan '.$selectedMonthLabel;
        } else {
            $period = (string) ($request->query('period') ?? $request->query('periode', 'all'));

            if (! array_key_exists($period, self::PERIODS)) {
                $period = 'all';
            }

            $startDate = $period === 'all'
                ? null
                : now()->subDays((int) $period - 1)->startOfDay();
            $periodLabel = self::PERIODS[$period];
        }

        // 2. Tentukan rentang grafik tren bulanan (?trend=6m|12m|year:YYYY)
        $requestedTrend = (string) $request->query('trend', '');
        if (array_key_exists($requestedTrend, $trendOptions)) {
            $trendRange = $requestedTrend;
        } elseif ($selectedMonth !== null) {
            $selectedYear = (int) substr($selectedMonth, 0, 4);
            $last12Start = now()->startOfMonth()->subMonths(11)->format('Y-m');
            $trendRange = ($selectedMonth >= $last12Start && $selectedMonth <= now()->format('Y-m'))
                ? '12m'
                : 'year:'.$selectedYear;
            if (! array_key_exists($trendRange, $trendOptions)) {
                $trendOptions[$trendRange] = 'Tahun '.$selectedYear;
            }
        } else {
            $trendRange = '12m';
        }

        if (str_starts_with($trendRange, 'year:')) {
            $trendYear = (int) substr($trendRange, 5);
            $monthlyTrend = Loan::monthlyTrend(12, $trendYear);
            $trendTitle = "Tren Peminjaman Tahun {$trendYear} (Jan–Des)";
        } elseif ($trendRange === '6m') {
            $monthlyTrend = Loan::monthlyTrend(6);
            $trendTitle = 'Tren Peminjaman 6 Bulan Terakhir';
        } else {
            $monthlyTrend = Loan::monthlyTrend(12);
            $trendTitle = 'Tren Peminjaman 12 Bulan Terakhir';
        }

        $selectedMonthWeeks = $selectedMonth !== null
            ? $this->weeklyBreakdownForMonth($startDate, $endDate)
            : collect();

        $borrowerStats = $this->borrowerStats($startDate, $endDate);
        $statusDistribution = $this->statusDistribution($startDate, $endDate);
        $conditionDistribution = $this->conditionDistribution();

        return view('statistics.index', [
            'period' => $period,
            'periods' => self::PERIODS,
            'periodLabel' => $periodLabel,
            'selectedMonth' => $selectedMonth,
            'selectedMonthLabel' => $selectedMonthLabel,
            'selectedMonthWeeks' => $selectedMonthWeeks,
            'availableMonths' => $availableMonths,
            'trendRange' => $trendRange,
            'trendOptions' => $trendOptions,
            'trendTitle' => $trendTitle,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summary' => $this->summary($startDate, $endDate),
            'statusDistribution' => $statusDistribution,
            'statusSunburst' => $this->statusSunburst($statusDistribution),
            'monthlyTrend' => $monthlyTrend,
            'topItems' => $this->topItems($startDate, $endDate),
            'topBorrowers' => $this->topBorrowers($borrowerStats),
            'lateBorrowers' => $this->lateBorrowers($borrowerStats),
            'topCategories' => $this->topCategories($startDate, $endDate),
            'conditionDistribution' => $conditionDistribution,
            'conditionSunburst' => $this->conditionSunburst(),
            'lowStockItems' => $this->lowStockItems(),
            'idleItems' => $this->idleItems($startDate, $endDate),
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
        ]);
    }

    /**
     * Validasi format bulan YYYY-MM.
     */
    private function resolveSelectedMonth(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $trimmed = trim($raw);
        if (! preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $trimmed, $matches)) {
            return null;
        }

        $year = (int) $matches[1];
        if ($year < 2000 || $year > 2100) {
            return null;
        }

        return $trimmed;
    }

    /**
     * Daftar tahun yang tersedia untuk filter grafik tren tahunan.
     */
    private function availableYears(): Collection
    {
        $currentYear = (int) now()->year;
        $loanYears = Loan::query()
            ->select('loan_date')
            ->get()
            ->map(fn (Loan $loan) => $loan->loan_date?->year)
            ->filter()
            ->unique()
            ->values();

        return $loanYears
            ->push($currentYear)
            ->unique()
            ->sortDesc()
            ->values();
    }

    /**
     * Daftar bulan tersedia (12 bulan terakhir + bulan dari histori transaksi) untuk pemilih bulan.
     */
    private function availableMonths(): Collection
    {
        $months = collect(range(0, 11))->map(fn (int $i) => now()->startOfMonth()->subMonths($i)->format('Y-m'));

        $loanMonths = Loan::query()
            ->select('loan_date')
            ->get()
            ->map(fn (Loan $loan) => $loan->loan_date?->format('Y-m'))
            ->filter()
            ->unique();

        return $months
            ->merge($loanMonths)
            ->unique()
            ->sortDesc()
            ->values()
            ->map(function (string $ym) {
                $date = Carbon::createFromFormat('Y-m-d', $ym.'-01');

                return [
                    'key' => $ym,
                    'label' => $date->translatedFormat('F Y'),
                ];
            });
    }

    /**
     * Pilihan rentang waktu pada komponen grafik tren bulanan.
     */
    private function buildTrendOptions(Collection $years): array
    {
        $options = [
            '6m' => '6 Bulan',
            '12m' => '12 Bulan',
        ];

        foreach ($years as $year) {
            $options['year:'.$year] = 'Tahun '.$year;
        }

        return $options;
    }

    /**
     * Rincian tren peminjaman per minggu di dalam satu bulan tertentu.
     */
    private function weeklyBreakdownForMonth(Carbon $monthStart, Carbon $monthEnd): Collection
    {
        $loans = $this->loansInPeriod($monthStart, $monthEnd)->get(['loan_date', 'status', 'quantity']);
        $daysInMonth = $monthEnd->day;
        $buckets = [
            [1, 7],
            [8, 14],
            [15, 21],
            [22, 28],
        ];
        if ($daysInMonth > 28) {
            $buckets[] = [29, $daysInMonth];
        }

        return collect($buckets)->map(function (array $range, int $index) use ($monthStart, $loans) {
            [$startDay, $endDay] = $range;
            $wStart = $monthStart->copy()->day($startDay)->startOfDay();
            $wEnd = $monthStart->copy()->day($endDay)->endOfDay();

            $weekLoans = $loans->filter(
                fn (Loan $loan) => $loan->loan_date !== null
                    && $loan->loan_date->gte($wStart)
                    && $loan->loan_date->lte($wEnd)
            );
            $approvedLoans = $weekLoans->whereIn('status', self::APPROVED_STATUSES);

            return [
                'label' => 'Minggu '.($index + 1),
                'date_range' => $startDay === $endDay
                    ? $wStart->translatedFormat('j M')
                    : $startDay.'–'.$wEnd->translatedFormat('j M'),
                'requests' => $weekLoans->count(),
                'approved' => $approvedLoans->count(),
                'units' => (int) $approvedLoans->sum('quantity'),
            ];
        });
    }

    /**
     * Query dasar peminjaman yang sudah difilter sesuai periode atau bulan.
     */
    private function loansInPeriod(?Carbon $startDate, ?Carbon $endDate = null): Builder
    {
        return Loan::query()
            ->when(
                $startDate,
                fn (Builder $query) => $query->whereDate('loan_date', '>=', $startDate->toDateString())
            )
            ->when(
                $endDate,
                fn (Builder $query) => $query->whereDate('loan_date', '<=', $endDate->toDateString())
            );
    }

    /**
     * Ringkasan angka utama (KPI).
     */
    private function summary(?Carbon $startDate, ?Carbon $endDate = null): array
    {
        $totalRequests = $this->loansInPeriod($startDate, $endDate)->count();

        $approvedQuery = $this->loansInPeriod($startDate, $endDate)->whereIn('status', self::APPROVED_STATUSES);
        $approvedCount = (clone $approvedQuery)->count();
        $unitsBorrowed = (int) (clone $approvedQuery)->sum('quantity');
        $activeBorrowers = (clone $approvedQuery)->distinct()->count('user_id');

        $rejectedCount = $this->loansInPeriod($startDate, $endDate)->where('status', 'ditolak')->count();
        $decidedCount = $approvedCount + $rejectedCount;

        // Peminjaman yang sudah dikembalikan beserta relasi catatan pengembaliannya.
        $returnedLoans = $this->loansInPeriod($startDate, $endDate)
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
    private function statusDistribution(?Carbon $startDate, ?Carbon $endDate = null): Collection
    {
        $counts = $this->loansInPeriod($startDate, $endDate)
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
    private function topItems(?Carbon $startDate, ?Carbon $endDate = null): Collection
    {
        return $this->loansInPeriod($startDate, $endDate)
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
    private function borrowerStats(?Carbon $startDate, ?Carbon $endDate = null): Collection
    {
        return $this->loansInPeriod($startDate, $endDate)
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
    private function topBorrowers(Collection $borrowerStats): Collection
    {
        return $borrowerStats
            ->sortByDesc(fn ($row) => [$row->loans_count, $row->units_total])
            ->take(5)
            ->values();
    }

    /**
     * Pengguna yang paling sering terlambat mengembalikan barang.
     */
    private function lateBorrowers(Collection $borrowerStats): Collection
    {
        return $borrowerStats
            ->filter(fn ($row) => (int) $row->late_count > 0)
            ->sortByDesc(fn ($row) => [$row->late_count, $row->loans_count])
            ->take(5)
            ->values();
    }

    /**
     * Kategori barang yang paling diminati menggunakan Eloquent Relationship.
     */
    private function topCategories(?Carbon $startDate, ?Carbon $endDate = null): Collection
    {
        return $this->loansInPeriod($startDate, $endDate)
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
     * Struktur hirarki 2-cincin (Sunburst) untuk Distribusi Status Peminjaman.
     * Cincin dalam: Kelompok Keputusan (Disetujui, Menunggu, Ditolak).
     * Cincin luar: Rincian Status (Dikembalikan, Dipinjam, Terlambat, Menunggu, Ditolak).
     */
    private function statusSunburst(Collection $statusDistribution): array
    {
        $byKey = $statusDistribution->keyBy('key');
        $dikembalikan = (int) ($byKey['dikembalikan']['count'] ?? 0);
        $dipinjam = (int) ($byKey['dipinjam']['count'] ?? 0);
        $terlambat = (int) ($byKey['terlambat']['count'] ?? 0);
        $menunggu = (int) ($byKey['menunggu']['count'] ?? 0);
        $ditolak = (int) ($byKey['ditolak']['count'] ?? 0);

        $approvedTotal = $dikembalikan + $dipinjam + $terlambat;
        $total = $approvedTotal + $menunggu + $ditolak;

        return [
            'total' => $total,
            'groups' => [
                [
                    'label' => 'Disetujui',
                    'sublabel' => 'Peminjaman Disetujui',
                    'count' => $approvedTotal,
                    'color' => '#2563eb',
                    'url' => route('loans.index'),
                    'children' => [
                        [
                            'label' => 'Dikembalikan',
                            'count' => $dikembalikan,
                            'color' => '#16a34a',
                            'url' => route('loans.index', ['status' => 'dikembalikan']),
                        ],
                        [
                            'label' => 'Dipinjam',
                            'count' => $dipinjam,
                            'color' => '#2563eb',
                            'url' => route('loans.index', ['status' => 'dipinjam']),
                        ],
                        [
                            'label' => 'Terlambat',
                            'count' => $terlambat,
                            'color' => '#dc2626',
                            'url' => route('loans.index', ['status' => 'terlambat']),
                        ],
                    ],
                ],
                [
                    'label' => 'Menunggu',
                    'sublabel' => 'Menunggu Persetujuan',
                    'count' => $menunggu,
                    'color' => '#d97706',
                    'url' => route('loans.index', ['status' => 'menunggu']),
                    'children' => [
                        [
                            'label' => 'Menunggu Verifikasi',
                            'count' => $menunggu,
                            'color' => '#d97706',
                            'url' => route('loans.index', ['status' => 'menunggu']),
                        ],
                    ],
                ],
                [
                    'label' => 'Ditolak',
                    'sublabel' => 'Pengajuan Ditolak',
                    'count' => $ditolak,
                    'color' => '#94a3b8',
                    'url' => route('loans.index', ['status' => 'ditolak']),
                    'children' => [
                        [
                            'label' => 'Pengajuan Ditolak',
                            'count' => $ditolak,
                            'color' => '#94a3b8',
                            'url' => route('loans.index', ['status' => 'ditolak']),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Struktur hirarki 2-cincin (Sunburst) untuk Kondisi & Ketersediaan Barang Inventaris.
     * Cincin dalam: Kondisi Fisik (Baik, Rusak Ringan, Rusak Berat).
     * Cincin luar: Rincian Ketersediaan Stok per Kondisi (tanpa warna neon).
     */
    private function conditionSunburst(): array
    {
        $items = Item::query()->get(['condition', 'stock']);
        $total = $items->count();

        $baikItems = $items->where('condition', 'baik');
        $baikReady = $baikItems->filter(fn (Item $item) => $item->stock > self::LOW_STOCK_THRESHOLD)->count();
        $baikLow = $baikItems->filter(fn (Item $item) => $item->stock > 0 && $item->stock <= self::LOW_STOCK_THRESHOLD)->count();
        $baikOut = $baikItems->filter(fn (Item $item) => $item->stock <= 0)->count();

        $ringanItems = $items->where('condition', 'rusak ringan');
        $ringanCount = $ringanItems->count();

        $beratItems = $items->where('condition', 'rusak berat');
        $beratCount = $beratItems->count();

        return [
            'total' => $total,
            'groups' => [
                [
                    'label' => 'Baik',
                    'sublabel' => 'Kondisi Baik',
                    'count' => $baikItems->count(),
                    'color' => '#16a34a',
                    'url' => route('items.index', ['condition' => 'baik']),
                    'children' => [
                        [
                            'label' => 'Baik • Stok Aman',
                            'count' => $baikReady,
                            'color' => '#16a34a',
                            'url' => route('items.index', ['condition' => 'baik', 'availability' => 'ready']),
                        ],
                        [
                            'label' => 'Baik • Stok Menipis',
                            'count' => $baikLow,
                            'color' => '#64748b',
                            'url' => route('items.index', ['condition' => 'baik', 'availability' => 'low']),
                        ],
                        [
                            'label' => 'Baik • Stok Habis',
                            'count' => $baikOut,
                            'color' => '#94a3b8',
                            'url' => route('items.index', ['condition' => 'baik', 'availability' => 'out']),
                            'show_in_legend' => $baikOut > 0,
                        ],
                    ],
                ],
                [
                    'label' => 'Rusak Ringan',
                    'sublabel' => 'Perlu Perbaikan Ringan',
                    'count' => $ringanCount,
                    'color' => '#d97706',
                    'url' => route('items.index', ['condition' => 'rusak ringan']),
                    'children' => [
                        [
                            'label' => 'Rusak Ringan',
                            'count' => $ringanCount,
                            'color' => '#d97706',
                            'url' => route('items.index', ['condition' => 'rusak ringan']),
                        ],
                    ],
                ],
                [
                    'label' => 'Rusak Berat',
                    'sublabel' => 'Tidak Layak Pinjam',
                    'count' => $beratCount,
                    'color' => '#dc2626',
                    'url' => route('items.index', ['condition' => 'rusak berat']),
                    'children' => [
                        [
                            'label' => 'Rusak Berat',
                            'count' => $beratCount,
                            'color' => '#dc2626',
                            'url' => route('items.index', ['condition' => 'rusak berat']),
                        ],
                    ],
                ],
            ],
        ];
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
    private function idleItems(?Carbon $startDate, ?Carbon $endDate = null): array
    {
        $query = Item::with('category')->whereDoesntHave('loans', function (Builder $loanQuery) use ($startDate, $endDate) {
            $loanQuery->whereIn('status', self::APPROVED_STATUSES)
                ->when($startDate, fn (Builder $q) => $q->whereDate('loan_date', '>=', $startDate->toDateString()))
                ->when($endDate, fn (Builder $q) => $q->whereDate('loan_date', '<=', $endDate->toDateString()));
        });

        return [
            'count' => (clone $query)->count(),
            'items' => $query->orderBy('name')->take(5)->get(),
        ];
    }
}
