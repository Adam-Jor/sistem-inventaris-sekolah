<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Tampilkan daftar pengguna dengan fitur pencarian, filter role, dan pagination.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $role = trim((string) $request->string('role'));
        $perPage = $this->resolvePerPage($request);

        $users = User::query()
            ->withCount([
                'loans',
                'loans as active_loans_count' => fn ($query) => $query->whereIn('status', ['dipinjam', 'terlambat']),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($role, ['admin', 'user'], true), function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('users.index', compact('users', 'search', 'role', 'perPage'));
    }

    /**
     * Tampilkan form tambah pengguna baru.
     */
    public function create(): View
    {
        return view('users.create');
    }

    /**
     * Simpan pengguna baru ke database.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        ActivityLogger::log(
            'create_user',
            "Menambahkan akun pengguna baru: {$user->name} ({$user->email}) dengan peran {$user->role}."
        );

        return redirect()
            ->route('users.index')
            ->with('success', "Akun pengguna \"{$user->name}\" berhasil ditambahkan.");
    }

    /**
     * Tampilkan form ubah data pengguna.
     */
    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Perbarui data pengguna di database.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user) && $request->validated('role') !== 'admin') {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Anda tidak dapat menurunkan peran (role) akun Anda sendiri.');
        }

        $data = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role' => $request->validated('role'),
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $user->update($data);

        ActivityLogger::log(
            'update_user',
            "Memperbarui akun pengguna: {$user->name} ({$user->email}), peran: {$user->role}."
        );

        return redirect()
            ->route('users.index')
            ->with('success', "Data pengguna \"{$user->name}\" berhasil diperbarui.");
    }

    /**
     * Hapus akun pengguna (Admin tidak boleh menghapus akunnya sendiri atau user dengan peminjaman aktif).
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return redirect()
                ->route('users.index')
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $activeLoansCount = $user->loans()
            ->whereIn('status', ['dipinjam', 'terlambat'])
            ->count();

        if ($activeLoansCount > 0) {
            return redirect()
                ->route('users.index')
                ->with('error', "Akun \"{$user->name}\" tidak dapat dihapus karena masih memiliki {$activeLoansCount} peminjaman barang yang belum dikembalikan.");
        }

        $name = $user->name;
        $email = $user->email;
        $role = $user->role;

        $user->delete();

        ActivityLogger::log(
            'delete_user',
            "Menghapus akun pengguna: {$name} ({$email}, peran: {$role})."
        );

        return redirect()
            ->route('users.index')
            ->with('success', "Akun pengguna \"{$name}\" berhasil dihapus.");
    }
}
