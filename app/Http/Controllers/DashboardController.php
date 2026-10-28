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
        $stats = [
            'total_items' => Item::count(),
            'total_categories' => Category::count(),
            'active_loans' => Loan::active()->count(),
            'waiting_loans' => Loan::waiting()->count(),
            'overdue_loans' => Loan::overdue()->count(),
        ];

        $recentLoans = Loan::with(['user', 'item.category'])
            ->latest()
            ->take(5)
            ->get();

        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(6)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentLoans', 'recentActivities'));
    }

    /**
     * Data & tampilan dashboard khusus User (Guru/Siswa).
     */
    protected function userDashboard($user): View
    {
        $stats = [
            'active_loans' => $user->loans()->active()->count(),
            'waiting_loans' => $user->loans()->waiting()->count(),
            'overdue_loans' => $user->loans()->overdue()->count(),
            'returned_loans' => $user->loans()->returned()->count(),
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

