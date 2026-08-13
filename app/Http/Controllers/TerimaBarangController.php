<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTerimaBarangRequest;
use App\Http\Requests\UpdateTerimaBarangRequest;
use App\Models\Produk;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\TerimaBarang;
use App\Models\TerimaBarangDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TerimaBarangController extends Controller
{
    /**
     * Daftar penerimaan barang, di-scope sesuai toko aktif.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = TerimaBarang::query()
            ->with(['supplier', 'store', 'details'])
            ->orderByDesc('tanggal');

        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('no_terima', 'ilike', "%{$search}%")
                    ->orWhere('no_surat_jalan', 'ilike', "%{$search}%")
                    ->orWhere('nama_supplier', 'ilike', "%{$search}%");
            });
        }

        $terima = $query->paginate($limit)->withQueryString();

        return Inertia::render('TerimaBarang/Index', [
            'terima' => $terima,
            'suppliers' => Supplier::orderBy('nama')->get(['id', 'kode', 'nama']),
            'produk' => Produk::with('stoks')
                ->orderBy('nama_barang')
                ->get(['id', 'kode_barang', 'nama_barang', 'harga_jual'])
                ->map(function ($p) use ($storeId) {
                    $sid = ($storeId && $storeId !== 'all') ? (int) $storeId : null;
                    $p->stok = $p->stokDi($sid);

                    return $p;
                }),
            'filters' => [
                'search' => $request->input('search', ''),
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Simpan penerimaan barang + detail + tambah stok (tanpa ubah harga beli master).
     */
    public function store(StoreTerimaBarangRequest $request): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        $store = Store::find(($storeId && $storeId !== 'all') ? $storeId : null);
        $tanggal = $validated['tanggal'] ?? now()->toDateString();

        try {
            $terima = DB::transaction(function () use ($validated, $store, $tanggal) {
                $items = $validated['items'] ?? [];

                $noTerima = (new TerimaBarang())->generateNoTerima($store?->kode, $tanggal);

                $supplier = isset($validated['supplier_id']) ? Supplier::find($validated['supplier_id']) : null;

                $terima = TerimaBarang::create([
                    'no_terima' => $noTerima,
                    'no_surat_jalan' => $validated['no_surat_jalan'] ?? null,
                    'tipe' => $validated['tipe'] ?? 'konsinyasi',
                    'supplier_id' => $validated['supplier_id'] ?? null,
                    'nama_supplier' => $validated['nama_supplier'] ?? $supplier?->nama,
                    'tanggal' => $tanggal,
                    'status' => $validated['status'] ?? 'selesai',
                    'keterangan' => $validated['keterangan'] ?? null,
                    'store_id' => $store?->id,
                ]);

                foreach ($items as $item) {
                    $produk = isset($item['produk_id']) ? Produk::find($item['produk_id']) : null;
                    $qty = (int) ($item['qty'] ?? 1);

                    TerimaBarangDetail::create([
                        'terima_barang_id' => $terima->id,
                        'produk_id' => $item['produk_id'] ?? null,
                        'nama_barang' => $item['nama_barang'] ?? $produk?->nama_barang,
                        'qty' => $qty,
                        'tanggal_expired' => $item['tanggal_expired'] ?? null,
                    ]);

                    // Barang diterima → stok MASUK (mutasi "masuk").
                    // Tidak ubah harga beli master (karena konsinyasi/retur = belum dibeli).
                    if ($produk && $qty > 0) {
                        $produk->tambahStok($qty, "Terima barang {$noTerima}", $store?->id);
                    }
                }

                return $terima;
            });

            return back()->with('success', "Penerimaan barang {$terima->no_terima} berhasil disimpan.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyimpan penerimaan barang: ' . $e->getMessage());
        }
    }

    /**
     * Update: rollback stok lama, hapus detail, simpan baru, tambah stok baru.
     */
    public function update(UpdateTerimaBarangRequest $request, TerimaBarang $terimaBarang): RedirectResponse
    {
        $storeId = session('store_id');
        $storeIdFinal = ($storeId && $storeId !== 'all') ? $storeId : null;
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($terimaBarang, $validated, $storeIdFinal) {
                // Kembalikan stok dari detail lama (kurangi)
                foreach ($terimaBarang->details as $detail) {
                    if ($detail->produk_id) {
                        $produk = Produk::find($detail->produk_id);
                        if ($produk) {
                            $produk->kurangiStok($detail->qty, "Reversi terima barang {$terimaBarang->no_terima}", $storeIdFinal);
                        }
                    }
                }

                $terimaBarang->details()->delete();

                $items = $validated['items'] ?? [];

                $terimaBarang->update([
                    'no_surat_jalan' => $validated['no_surat_jalan'] ?? null,
                    'tipe' => $validated['tipe'] ?? $terimaBarang->tipe,
                    'supplier_id' => $validated['supplier_id'] ?? null,
                    'nama_supplier' => $validated['nama_supplier'] ?? null,
                    'tanggal' => $validated['tanggal'] ?? $terimaBarang->tanggal,
                    'status' => $validated['status'] ?? $terimaBarang->status,
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);

                foreach ($items as $item) {
                    $produk = isset($item['produk_id']) ? Produk::find($item['produk_id']) : null;
                    $qty = (int) ($item['qty'] ?? 1);

                    TerimaBarangDetail::create([
                        'terima_barang_id' => $terimaBarang->id,
                        'produk_id' => $item['produk_id'] ?? null,
                        'nama_barang' => $item['nama_barang'] ?? $produk?->nama_barang,
                        'qty' => $qty,
                        'tanggal_expired' => $item['tanggal_expired'] ?? null,
                    ]);

                    if ($produk && $qty > 0) {
                        $produk->tambahStok($qty, "Terima barang {$terimaBarang->no_terima}", $storeIdFinal);
                    }
                }
            });

            return back()->with('success', "Penerimaan barang {$terimaBarang->no_terima} berhasil diperbarui.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memperbarui penerimaan barang: ' . $e->getMessage());
        }
    }

    /**
     * Hapus penerimaan barang + kembalikan stok.
     */
    public function destroy(TerimaBarang $terimaBarang): RedirectResponse
    {
        $storeId = session('store_id');
        $storeIdFinal = ($storeId && $storeId !== 'all') ? $storeId : null;

        try {
            DB::transaction(function () use ($terimaBarang, $storeIdFinal) {
                foreach ($terimaBarang->details as $detail) {
                    if ($detail->produk_id) {
                        $produk = Produk::find($detail->produk_id);
                        if ($produk) {
                            $produk->kurangiStok($detail->qty, "Hapus terima barang {$terimaBarang->no_terima}", $storeIdFinal);
                        }
                    }
                }

                $terimaBarang->delete();
            });

            return back()->with('success', "Penerimaan barang {$terimaBarang->no_terima} berhasil dihapus.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus penerimaan barang: ' . $e->getMessage());
        }
    }
}
