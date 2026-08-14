<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKonsinyiRequest;
use App\Http\Requests\UpdateKonsinyiRequest;
use App\Models\Konsinyi;
use App\Models\KonsinyiDetail;
use App\Models\Produk;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KonsinyiController extends Controller
{
    /**
     * Daftar retur & pembayaran barang konsinyi, di-scope sesuai toko aktif.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = Konsinyi::query()
            ->with(['supplier', 'user', 'store', 'details'])
            ->orderByDesc('tgl_transaksi')
            ->orderByDesc('id');

        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('no_transaksi', 'ilike', "%{$search}%")
                    ->orWhere('no_bukti', 'ilike', "%{$search}%")
                    ->orWhere('no_faktur', 'ilike', "%{$search}%")
                    ->orWhere('nama_supplier', 'ilike', "%{$search}%")
                    ->orWhere('kode_supplier', 'ilike', "%{$search}%");
            });
        }

        if ($jenis = $request->input('jenis')) {
            $query->where('jenis', $jenis);
        }

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        if ($tanggalMulai && $tanggalSelesai) {
            $query->whereBetween('tgl_transaksi', [$tanggalMulai, $tanggalSelesai]);
        } elseif ($tanggalMulai) {
            $query->whereDate('tgl_transaksi', '>=', $tanggalMulai);
        } elseif ($tanggalSelesai) {
            $query->whereDate('tgl_transaksi', '<=', $tanggalSelesai);
        }

        $konsinyi = $query->paginate($limit)->withQueryString();

        return Inertia::render('Konsinyi/Index', [
            'konsinyi' => $konsinyi,
            'suppliers' => Supplier::orderBy('nama')->get(['id', 'kode', 'nama']),
            'produks' => Produk::orderBy('nama_barang')->get(['id', 'kode_barang', 'nama_barang', 'satuan', 'harga_beli', 'harga_jual']),
            'filters' => [
                'search' => $request->input('search', ''),
                'jenis' => $jenis ?? '',
                'supplier_id' => $supplierId ?? '',
                'tanggal_mulai' => $tanggalMulai ?? '',
                'tanggal_selesai' => $tanggalSelesai ?? '',
                'limit' => $limit,
            ],
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
        ]);
    }

    /**
     * Simpan transaksi konsinyi (header + detail item) dalam 1 transaksi DB.
     */
    public function store(StoreKonsinyiRequest $request): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $storeId) {
                $konsinyi = Konsinyi::create([
                    'no_transaksi' => $validated['no_transaksi'],
                    'jenis' => $validated['jenis'],
                    'tgl_transaksi' => $validated['tgl_transaksi'],
                    'no_bukti' => $validated['no_bukti'],
                    'no_faktur' => $validated['no_faktur'] ?? null,
                    'supplier_id' => $validated['supplier_id'] ?? null,
                    'kode_supplier' => $validated['kode_supplier'] ?? null,
                    'nama_supplier' => $validated['nama_supplier'] ?? null,
                    'alamat' => $validated['alamat'] ?? null,
                    'no_telp' => $validated['no_telp'] ?? null,
                    'contact_person' => $validated['contact_person'] ?? null,
                    'diskon' => $validated['diskon'] ?? 0,
                    'total' => $validated['total'],
                    'jumlah_bayar' => $validated['jumlah_bayar'],
                    'kurang_bayar' => $validated['kurang_bayar'] ?? 0,
                    'keterangan' => $validated['keterangan'] ?? null,
                    'store_id' => ($storeId && $storeId !== 'all') ? $storeId : null,
                    'user_id' => auth()->id(),
                ]);

                foreach ($validated['items'] as $item) {
                    KonsinyiDetail::create([
                        'konsinyi_id' => $konsinyi->id,
                        'produk_id' => $item['produk_id'] ?? null,
                        'kode_barang' => $item['kode_barang'] ?? null,
                        'nama_barang' => $item['nama_barang'] ?? null,
                        'satuan' => $item['satuan'] ?? null,
                        'qty' => $item['qty'],
                        'harga_beli' => $item['harga_beli'],
                        'harga_jual' => $item['harga_jual'] ?? 0,
                        'subtotal' => $item['subtotal'],
                    ]);
                }
            });

            $msg = $validated['jenis'] === 'retur'
                ? 'Retur barang konsinyi berhasil dicatat.'
                : 'Pembayaran barang konsinyi berhasil dicatat.';

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyimpan konsinyi: ' . $e->getMessage());
        }
    }

    /**
     * Perbarui transaksi konsinyi (header + detail).
     */
    public function update(UpdateKonsinyiRequest $request, Konsinyi $konsinyi): RedirectResponse
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $konsinyi) {
                $konsinyi->update([
                    'jenis' => $validated['jenis'],
                    'tgl_transaksi' => $validated['tgl_transaksi'],
                    'no_bukti' => $validated['no_bukti'],
                    'no_faktur' => $validated['no_faktur'] ?? null,
                    'supplier_id' => $validated['supplier_id'] ?? null,
                    'kode_supplier' => $validated['kode_supplier'] ?? null,
                    'nama_supplier' => $validated['nama_supplier'] ?? null,
                    'alamat' => $validated['alamat'] ?? null,
                    'no_telp' => $validated['no_telp'] ?? null,
                    'contact_person' => $validated['contact_person'] ?? null,
                    'diskon' => $validated['diskon'] ?? 0,
                    'total' => $validated['total'],
                    'jumlah_bayar' => $validated['jumlah_bayar'],
                    'kurang_bayar' => $validated['kurang_bayar'] ?? 0,
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);

                $konsinyi->details()->delete();

                foreach ($validated['items'] as $item) {
                    KonsinyiDetail::create([
                        'konsinyi_id' => $konsinyi->id,
                        'produk_id' => $item['produk_id'] ?? null,
                        'kode_barang' => $item['kode_barang'] ?? null,
                        'nama_barang' => $item['nama_barang'] ?? null,
                        'satuan' => $item['satuan'] ?? null,
                        'qty' => $item['qty'],
                        'harga_beli' => $item['harga_beli'],
                        'harga_jual' => $item['harga_jual'] ?? 0,
                        'subtotal' => $item['subtotal'],
                    ]);
                }
            });

            return back()->with('success', 'Data konsinyi berhasil diperbarui.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memperbarui konsinyi: ' . $e->getMessage());
        }
    }

    /**
     * Hapus transaksi konsinyi (detail ikut terhapus via cascade).
     */
    public function destroy(Konsinyi $konsinyi): RedirectResponse
    {
        $konsinyi->delete();

        return back()->with('success', 'Data konsinyi berhasil dihapus.');
    }
}
