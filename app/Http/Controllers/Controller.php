<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Daftar opsi jumlah data per halaman yang diizinkan.
     *
     * @var array<int, int>
     */
    public const PER_PAGE_OPTIONS = [10, 20, 50, 100];

    /**
     * Ambil nilai per_page yang valid dari request (default: 10).
     */
    protected function resolvePerPage(Request $request, int $default = 10): int
    {
        $perPage = $request->integer('per_page', $default);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : $default;
    }
}
