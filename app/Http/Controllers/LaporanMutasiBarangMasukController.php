<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Store;
use App\Models\StokRiwayat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaporanMutasiBarangMasukController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->toDateString());
        $produkId = $request->input('produk_id');
        $tokoId = $request->input('toko_id');
        $search = trim((string) $request->input('search', ''));

        // ====== Base query factory (tipe = 'masuk' only) ======
        $baseQuery = function () use ($storeId, $dari, $sampai, $produkId, $tokoId, $search) {
            $q = StokRiwayat::query()
                ->where('stok_riwayat.tipe', 'masuk')
                ->whereBetween('stok_riwayat.tanggal', [$dari, $sampai]);

            if ($storeId && $storeId !== 'all') {
                $q->where('stok_riwayat.store_id', $storeId);
            }
            if ($tokoId && $tokoId !== 'all') {
                $q->where('stok_riwayat.store_id', $tokoId);
            }
            if ($produkId && $produkId !== 'all') {
                $q->where('stok_riwayat.produk_id', $produkId);
            }
            if ($search) {
                $q->whereHas('produk', function ($sq) use ($search) {
                    $sq->where('nama_barang', 'ilike', "%{$search}%")
                        ->orWhere('kode_barang', 'ilike', "%{$search}%");
                });
            }

            return $q;
        };

        // ====== Agregasi total ======
        $totals = $baseQuery()->selectRaw('
            COUNT(*) as jumlah_transaksi,
            COALESCE(SUM(stok_riwayat.qty), 0) as total_qty
        ')->first();

        // ====== Rekap per Produk ======
        $perProduk = $baseQuery()
            ->selectRaw('
                stok_riwayat.produk_id,
                COALESCE(produk.nama_barang, ?) as nama_produk,
                COALESCE(produk.kode_barang, ?) as kode_produk,
                COUNT(*) as jumlah,
                COALESCE(SUM(stok_riwayat.qty), 0) as total_qty
            ', ['Tanpa Produk', '-'])
            ->leftJoin('produk', 'stok_riwayat.produk_id', '=', 'produk.id')
            ->groupBy('stok_riwayat.produk_id', 'produk.nama_barang', 'produk.kode_barang')
            ->orderByDesc('total_qty')
            ->get();

        // ====== Rekap per Toko ======
        $perToko = $baseQuery()
            ->selectRaw('
                stok_riwayat.store_id,
                COALESCE(stores.nama, ?) as nama_toko,
                COUNT(*) as jumlah,
                COALESCE(SUM(stok_riwayat.qty), 0) as total_qty
            ', ['Pusat'])
            ->leftJoin('stores', 'stok_riwayat.store_id', '=', 'stores.id')
            ->groupBy('stok_riwayat.store_id', 'stores.nama')
            ->orderByDesc('total_qty')
            ->get();

        // ====== Detail transaksi ======
        $detailTransaksi = $baseQuery()
            ->with(['produk:id,kode_barang,nama_barang', 'store:id,nama'])
            ->select('stok_riwayat.*')
            ->orderByDesc('stok_riwayat.tanggal')
            ->orderByDesc('stok_riwayat.id')
            ->paginate(100)
            ->withQueryString();

        return Inertia::render('LaporanMutasiBarangMasuk/Index', [
            'totals' => $totals,
            'perProduk' => $perProduk,
            'perToko' => $perToko,
            'detailTransaksi' => $detailTransaksi,
            'produks' => Produk::orderBy('nama_barang')->get(['id', 'kode_barang', 'nama_barang']),
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
            'filters' => [
                'dari' => $dari,
                'sampai' => $sampai,
                'produk_id' => $produkId ?? '',
                'toko_id' => $tokoId ?? '',
                'search' => $search,
            ],
        ]);
    }
}
