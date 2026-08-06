<?php

namespace App\Http\Controllers;

use App\Exports\ProdukExport;
use App\Http\Requests\ImportProdukRequest;
use App\Http\Requests\StoreProdukRequest;
use App\Http\Requests\UpdateProdukRequest;
use App\Imports\ProdukImport;
use App\Models\Produk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProdukController extends Controller
{
    /**
     * Menampilkan daftar produk, di-scope sesuai toko aktif.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        // Limit data per halaman (whitelist, default 10)
        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = Produk::query()
            ->with(['store', 'supplier'])
            ->orderByDesc('created_at');

        // Scope ke toko aktif (kecuali 'all' / pusat)
        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'ilike', "%{$search}%")
                    ->orWhere('kode_barang', 'ilike', "%{$search}%")
                    ->orWhere('kategori', 'ilike', "%{$search}%");
            });
        }

        $produk = $query->paginate($limit)->withQueryString();

        return Inertia::render('Produk/Index', [
            'produk' => $produk,
            'suppliers' => \App\Models\Supplier::orderBy('nama')->get(['id', 'kode', 'nama']),
            'filters' => [
                'search' => $request->input('search', ''),
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Simpan produk baru.
     */
    public function store(StoreProdukRequest $request): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        $data = array_merge($validated, [
            'store_id' => ($storeId && $storeId !== 'all') ? $storeId : null,
        ]);

        Produk::create($data);

        return back()->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Perbarui produk.
     */
    public function update(UpdateProdukRequest $request, Produk $produk): RedirectResponse
    {
        $produk->update($request->validated());

        return back()->with('success', 'Data produk berhasil diperbarui.');
    }

    /**
     * Hapus produk.
     */
    public function destroy(Produk $produk): RedirectResponse
    {
        $produk->delete();

        return back()->with('success', 'Produk berhasil dihapus.');
    }

    /**
     * Export data produk ke Excel (sesuai filter aktif).
     * Dengan ?template=1 menghasilkan template kosong (hanya heading).
     */
    public function export(Request $request): BinaryFileResponse
    {
        $storeId = session('store_id');

        $filters = [
            'store_id' => $storeId,
            'search' => $request->input('search', ''),
            'template' => $request->boolean('template'),
        ];

        $namaFile = $request->boolean('template')
            ? 'template-produk.xlsx'
            : 'data-produk-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new ProdukExport($filters), $namaFile);
    }

    /**
     * Import data produk dari file Excel/CSV.
     */
    public function import(ImportProdukRequest $request): RedirectResponse
    {
        $storeId = session('store_id');

        $import = new ProdukImport(($storeId && $storeId !== 'all') ? $storeId : null);
        Excel::import($import, $request->file('file'));

        $message = "Import selesai: {$import->imported} berhasil, {$import->skipped} dilewati.";

        if (! empty($import->errors)) {
            $detail = implode('; ', array_slice($import->errors, 0, 5));
            $message .= ' ' . $detail;
        }

        return back()->with('success', $message);
    }
}
