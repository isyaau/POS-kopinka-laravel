<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequest;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    /**
     * Menampilkan daftar toko dengan pencarian & filter.
     */
    public function index(Request $request): Response
    {
        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = Store::query()->orderByDesc('created_at')->orderByDesc('id');

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'ilike', "%{$search}%")
                    ->orWhere('kode', 'ilike', "%{$search}%")
                    ->orWhere('alamat', 'ilike', "%{$search}%")
                    ->orWhere('telepon', 'ilike', "%{$search}%");
            });
        }

        if ($tipe = $request->input('tipe')) {
            if (in_array($tipe, ['pusat', 'toko'], true)) {
                $query->where('tipe', $tipe);
            }
        }

        $stores = $query->paginate($limit)->withQueryString();

        return Inertia::render('Toko/Index', [
            'stores' => $stores,
            'filters' => [
                'search' => $request->input('search', ''),
                'tipe' => $request->input('tipe', ''),
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Simpan toko baru.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        Store::create($request->validated());

        return back()->with('success', 'Toko berhasil ditambahkan.');
    }

    /**
     * Perbarui toko.
     */
    public function update(StoreRequest $request, Store $store): RedirectResponse
    {
        $store->update($request->validated());

        return back()->with('success', 'Data toko berhasil diperbarui.');
    }

    /**
     * Hapus toko (kecuali pusat).
     */
    public function destroy(Store $store): RedirectResponse
    {
        if ($store->tipe === 'pusat') {
            return back()->with('error', 'Toko pusat tidak dapat dihapus.');
        }

        $store->delete();

        return back()->with('success', 'Toko berhasil dihapus.');
    }
}
