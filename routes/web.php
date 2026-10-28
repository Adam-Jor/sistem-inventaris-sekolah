<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReturnLogController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Khusus Admin
    |--------------------------------------------------------------------------
    | PENTING: grup ini harus didaftarkan SEBELUM route bersama di bawah,
    | supaya /items/create dan /loans/create tidak tertangkap oleh /items/{item}
    | dan /loans/{loan}.
    */
    Route::middleware('role:admin')->group(function () {
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('items', ItemController::class)->except(['index', 'show']);

        // Verifikasi pengajuan peminjaman
        Route::patch('loans/{loan}/approve', [LoanController::class, 'approve'])->name('loans.approve');
        Route::patch('loans/{loan}/reject', [LoanController::class, 'reject'])->name('loans.reject');
        Route::delete('loans/{loan}', [LoanController::class, 'destroy'])->name('loans.destroy');

        // Pencatatan pengembalian
        Route::get('loans/{loan}/return', [ReturnLogController::class, 'create'])->name('loans.return.create');
        Route::post('loans/{loan}/return', [ReturnLogController::class, 'store'])->name('loans.return.store');
        Route::resource('return-logs', ReturnLogController::class)->only(['index', 'show']);

        Route::resource('users', UserController::class)->except('show');
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin & User
    |--------------------------------------------------------------------------
    | Controller yang sama dipakai kedua role; isi datanya dibedakan di controller
    | (contoh: User hanya melihat peminjaman miliknya, Admin melihat semua).
    */
    Route::middleware('role:admin,user')->group(function () {
        Route::resource('items', ItemController::class)->only(['index', 'show']);
        Route::resource('loans', LoanController::class)->only(['index', 'create', 'store', 'show']);
    });
});

require __DIR__.'/auth.php';
