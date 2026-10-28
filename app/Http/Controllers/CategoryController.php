<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Tampilkan daftar kategori dengan pencarian dan pagination.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $categories = Category::query()
            ->withCount('items')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('categories.index', compact('categories', 'search'));
    }

    /**
     * Tampilkan form tambah kategori baru.
     */
    public function create(): View
    {
        return view('categories.create');
    }

    /**
     * Simpan kategori baru ke database.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        ActivityLogger::log(
            'create_category',
            "Menambahkan kategori {$category->name}."
        );

        return redirect()
            ->route('categories.index')
            ->with('success', "Kategori \"{$category->name}\" berhasil ditambahkan.");
    }

    /**
     * Tampilkan form edit kategori.
     */
    public function edit(Category $category): View
    {
        return view('categories.edit', compact('category'));
    }

    /**
     * Perbarui data kategori di database.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $oldName = $category->name;
        $category->update($request->validated());

        $description = $oldName !== $category->name
            ? "Mengubah kategori {$oldName} menjadi {$category->name}."
            : "Memperbarui kategori {$category->name}.";

        ActivityLogger::log('update_category', $description);

        return redirect()
            ->route('categories.index')
            ->with('success', "Kategori \"{$category->name}\" berhasil diperbarui.");
    }

    /**
     * Hapus kategori jika tidak lagi memiliki barang.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $itemsCount = $category->items()->withTrashed()->count();

        if ($itemsCount > 0) {
            return redirect()
                ->route('categories.index')
                ->with(
                    'error',
                    "Kategori \"{$category->name}\" tidak dapat dihapus karena masih memiliki {$itemsCount} barang terkait."
                );
        }

        $categoryName = $category->name;
        $category->delete();

        ActivityLogger::log(
            'delete_category',
            "Menghapus kategori {$categoryName}."
        );

        return redirect()
            ->route('categories.index')
            ->with('success', "Kategori \"{$categoryName}\" berhasil dihapus.");
    }
}
