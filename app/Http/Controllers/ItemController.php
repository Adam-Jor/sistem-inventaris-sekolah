<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Category;
use App\Models\Item;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemController extends Controller
{
    /**
     * Tampilkan daftar barang dengan pencarian (nama/kode) dan filter (kategori, kondisi).
     * Dapat diakses oleh Admin maupun User.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $categoryId = $request->input('category_id', '');
        $condition = $request->string('condition')->trim()->toString();
        $perPage = $this->resolvePerPage($request);

        $items = Item::query()
            ->with('category')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($categoryId !== '' && $categoryId !== null, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($condition !== '', function ($query) use ($condition) {
                $query->where('condition', $condition);
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return view('items.index', compact('items', 'categories', 'search', 'categoryId', 'condition', 'perPage'));
    }

    /**
     * Tampilkan form tambah barang baru (khusus Admin).
     */
    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('items.create', compact('categories'));
    }

    /**
     * Simpan barang baru ke database (khusus Admin).
     */
    public function store(StoreItemRequest $request): RedirectResponse
    {
        $item = Item::create($request->validated());

        ActivityLogger::log(
            'create_item',
            "Menambahkan barang {$item->name} ({$item->code})."
        );

        return redirect()
            ->route('items.index')
            ->with('success', "Barang \"{$item->name}\" ({$item->code}) berhasil ditambahkan.");
    }

    /**
     * Tampilkan detail barang beserta riwayat peminjamannya.
     */
    public function show(Request $request, Item $item): View
    {
        $item->load('category');

        $loansQuery = $item->loans()->with('user')->latest();

        if (! $request->user()->isAdmin()) {
            $loansQuery->where('user_id', $request->user()->id);
        }

        $recentLoans = $loansQuery->take(5)->get();

        return view('items.show', compact('item', 'recentLoans'));
    }

    /**
     * Tampilkan form edit barang (khusus Admin).
     */
    public function edit(Item $item): View
    {
        $categories = Category::orderBy('name')->get();

        return view('items.edit', compact('item', 'categories'));
    }

    /**
     * Perbarui data barang di database (khusus Admin).
     */
    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $item->update($request->validated());

        ActivityLogger::log(
            'update_item',
            "Memperbarui barang {$item->name} ({$item->code})."
        );

        return redirect()
            ->route('items.index')
            ->with('success', "Barang \"{$item->name}\" ({$item->code}) berhasil diperbarui.");
    }

    /**
     * Soft delete barang (khusus Admin).
     */
    public function destroy(Item $item): RedirectResponse
    {
        $itemName = $item->name;
        $itemCode = $item->code;

        $item->delete();

        ActivityLogger::log(
            'delete_item',
            "Menghapus barang {$itemName} ({$itemCode})."
        );

        return redirect()
            ->route('items.index')
            ->with('success', "Barang \"{$itemName}\" ({$itemCode}) berhasil dihapus.");
    }
}
