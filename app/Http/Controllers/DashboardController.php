<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Item;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard sesuai role pengguna (Admin atau User).
     */
    public function index(Request $request): View
    {
        Loan::syncOverdueStatuses();

        $user = $request->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }

        return $this->userDashboard($user);
    }

    /**
     * Data & tampilan dashboard khusus Admin.
     */
    protected function adminDashboard(): View
    {
        $today = now()->toDateString();
        $loanCounts = Loan::query()
            ->selectRaw("
                COALESCE(SUM(CASE WHEN status = 'dipinjam' THEN 1 ELSE 0 END), 0) as active_loans,
                COALESCE(SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END), 0) as waiting_loans,
                COALESCE(SUM(CASE WHEN status = 'terlambat' OR (status = 'dipinjam' AND due_date < ?) THEN 1 ELSE 0 END), 0) as overdue_loans
            ", [$today])
            ->first();

        $stats = [
            'total_items' => Item::count(),
            'total_categories' => Category::count(),
            'active_loans' => (int) ($loanCounts->active_loans ?? 0),
            'waiting_loans' => (int) ($loanCounts->waiting_loans ?? 0),
            'overdue_loans' => (int) ($loanCounts->overdue_loans ?? 0),
        ];

        $recentLoans = Loan::with(['user', 'item.category'])
            ->latest()
            ->take(5)
            ->get();

        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(6)
            ->get();

        $monthlyTrend = Loan::monthlyTrend();

        return view('admin.dashboard', compact('stats', 'recentLoans', 'recentActivities', 'monthlyTrend'));
    }

    /**
     * Data & tampilan dashboard khusus User (Guru/Siswa).
     */
    protected function userDashboard($user): View
    {
        $today = now()->toDateString();
        $loanCounts = $user->loans()
            ->selectRaw("
                COALESCE(SUM(CASE WHEN status = 'dipinjam' THEN 1 ELSE 0 END), 0) as active_loans,
                COALESCE(SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END), 0) as waiting_loans,
                COALESCE(SUM(CASE WHEN status = 'terlambat' OR (status = 'dipinjam' AND due_date < ?) THEN 1 ELSE 0 END), 0) as overdue_loans,
                COALESCE(SUM(CASE WHEN status = 'dikembalikan' THEN 1 ELSE 0 END), 0) as returned_loans
            ", [$today])
            ->first();

        $stats = [
            'active_loans' => (int) ($loanCounts->active_loans ?? 0),
            'waiting_loans' => (int) ($loanCounts->waiting_loans ?? 0),
            'overdue_loans' => (int) ($loanCounts->overdue_loans ?? 0),
            'returned_loans' => (int) ($loanCounts->returned_loans ?? 0),
        ];

        $nearestDueLoans = $user->loans()
            ->with('item.category')
            ->whereIn('status', ['dipinjam', 'terlambat'])
            ->orderBy('due_date')
            ->take(5)
            ->get();

        $recentLoans = $user->loans()
            ->with('item.category')
            ->latest()
            ->take(5)
            ->get();

        return view('user.dashboard', compact('stats', 'nearestDueLoans', 'recentLoans'));
    }
}

