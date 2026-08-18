<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegisterLabelEtalaseBarangRequest;
use App\Http\Requests\UpdateRegisterLabelEtalaseBarangRequest;
use App\Models\Produk;
use App\Models\RegisterLabelEtalaseBarang;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisterLabelEtalaseBarangController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = RegisterLabelEtalaseBarang::query()
            ->with(['produk', 'user', 'store'])
            ->orderByDesc('tgl_register')
            ->orderByDesc('id');

        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('no_transaksi', 'ilike', "%{$search}%")
                    ->orWhere('no_bukti', 'ilike', "%{$search}%")
                    ->orWhere('kode_barang', 'ilike', "%{$search}%")
                    ->orWhere('nama_barang', 'ilike', "%{$search}%")
                    ->orWhere('kategori', 'ilike', "%{$search}%")
                    ->orWhere('no_rak', 'ilike', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($kategori = $request->input('kategori')) {
            $query->where('kategori', 'ilike', $kategori);
        }

        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        if ($tanggalMulai && $tanggalSelesai) {
            $query->whereBetween('tgl_register', [$tanggalMulai, $tanggalSelesai]);
        } elseif ($tanggalMulai) {
            $query->whereDate('tgl_register', '>=', $tanggalMulai);
        } elseif ($tanggalSelesai) {
            $query->whereDate('tgl_register', '<=', $tanggalSelesai);
        }

        $labels = $query->paginate($limit)->withQueryString();

        return Inertia::render('RegisterLabelEtalaseBarang/Index', [
            'labels' => $labels,
            'produks' => Produk::orderBy('nama_barang')->get(['id', 'kode_barang', 'nama_barang', 'kategori', 'satuan', 'no_rak', 'harga_jual']),
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $status ?? '',
                'kategori' => $kategori ?? '',
                'tanggal_mulai' => $tanggalMulai ?? '',
                'tanggal_selesai' => $tanggalSelesai ?? '',
                'limit' => $limit,
            ],
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
        ]);
    }

    public function store(StoreRegisterLabelEtalaseBarangRequest $request): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        // Auto-fill data produk dari relasi
        $produk = Produk::find($validated['produk_id']);
        if ($produk) {
            $validated['kode_barang'] = $validated['kode_barang'] ?? $produk->kode_barang;
            $validated['nama_barang'] = $validated['nama_barang'] ?? $produk->nama_barang;
            $validated['kategori'] = $validated['kategori'] ?? $produk->kategori;
            $validated['satuan'] = $validated['satuan'] ?? $produk->satuan;
            $validated['no_rak'] = $validated['no_rak'] ?? $produk->no_rak;
            $validated['harga_jual'] = $validated['harga_jual'] ?? $produk->harga_jual;
        }

        $data = array_merge($validated, [
            'store_id' => ($storeId && $storeId !== 'all') ? $storeId : null,
            'user_id' => auth()->id(),
        ]);

        RegisterLabelEtalaseBarang::create($data);

        return back()->with('success', 'Register label etalase barang berhasil dicatat.');
    }

    public function update(UpdateRegisterLabelEtalaseBarangRequest $request, RegisterLabelEtalaseBarang $registerLabelEtalaseBarang): RedirectResponse
    {
        $registerLabelEtalaseBarang->update($request->validated());

        return back()->with('success', 'Data register label etalase barang berhasil diperbarui.');
    }

    public function destroy(RegisterLabelEtalaseBarang $registerLabelEtalaseBarang): RedirectResponse
    {
        $registerLabelEtalaseBarang->delete();

        return back()->with('success', 'Data register label etalase barang berhasil dihapus.');
    }
}
