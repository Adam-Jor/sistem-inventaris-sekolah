<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Tampilkan daftar activity log (read-only) dengan pencarian, filter user/aksi, dan pagination (LOG-02 & LOG-03).
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $userId = $request->input('user_id', '');
        $action = $request->string('action')->trim()->toString();
        $perPage = $this->resolvePerPage($request);

        $logs = ActivityLog::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('description', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($userId !== '' && $userId !== null, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->when($action !== '', function ($query) use ($action) {
                $query->where('action', $action);
            })
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $users = User::orderBy('name')->get();
        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('activity-logs.index', compact(
            'logs',
            'users',
            'actions',
            'search',
            'userId',
            'action',
            'perPage'
        ));
    }
}
