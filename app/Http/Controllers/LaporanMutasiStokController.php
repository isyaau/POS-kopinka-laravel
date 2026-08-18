<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Store;
use App\Models\Stok;
use App\Models\StokRiwayat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaporanMutasiStokController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = session('store_id');
        $tab = $request->input('tab', 'kartu');

        // ====== Filters ======
        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->toDateString());
        $produkId = $request->input('produk_id');
        $tokoId = $request->input('toko_id');
        $search = trim((string) $request->input('search', ''));

        // ====== KARTU STOK: current stock levels ======
        $kartuQuery = Stok::query()
            ->with(['produk:id,kode_barang,nama_barang,harga_beli,harga_jual', 'store:id,nama,kode'])
            ->orderByDesc('stok.stok');

        if ($storeId && $storeId !== 'all') {
            $kartuQuery->where('stok.store_id', $storeId);
        }
        if ($tokoId && $tokoId !== 'all') {
            $kartuQuery->where('stok.store_id', $tokoId);
        }
        if ($produkId && $produkId !== 'all') {
            $kartuQuery->where('stok.produk_id', $produkId);
        }
        if ($search) {
            $kartuQuery->whereHas('produk', function ($q) use ($search) {
                $q->where('nama_barang', 'ilike', "%{$search}%")
                    ->orWhere('kode_barang', 'ilike', "%{$search}%");
            });
        }

        $kartuStok = $kartuQuery->paginate(100)->withQueryString();

        // ====== Agregasi kartu stok ======
        $kartuAgg = Stok::query()
            ->selectRaw('
                COUNT(*) as jumlah_produk,
                COALESCE(SUM(stok), 0) as total_stok,
                COALESCE(SUM(CASE WHEN stok <= stok_minimum THEN 1 ELSE 0 END), 0) as stok_menipis,
                COALESCE(SUM(CASE WHEN stok = 0 THEN 1 ELSE 0 END), 0) as stok_habis
            ');
        if ($storeId && $storeId !== 'all') {
            $kartuAgg->where('stok.store_id', $storeId);
        }
        if ($tokoId && $tokoId !== 'all') {
            $kartuAgg->where('stok.store_id', $tokoId);
        }
        $kartuSummary = $kartuAgg->first();

        // ====== MUTASI STOK: movement history ======
        $mutasiQuery = StokRiwayat::query()
            ->with(['produk:id,kode_barang,nama_barang', 'store:id,nama'])
            ->whereBetween('stok_riwayat.tanggal', [$dari, $sampai])
            ->orderByDesc('stok_riwayat.tanggal')
            ->orderByDesc('stok_riwayat.id');

        if ($storeId && $storeId !== 'all') {
            $mutasiQuery->where('stok_riwayat.store_id', $storeId);
        }
        if ($tokoId && $tokoId !== 'all') {
            $mutasiQuery->where('stok_riwayat.store_id', $tokoId);
        }
        if ($produkId && $produkId !== 'all') {
            $mutasiQuery->where('stok_riwayat.produk_id', $produkId);
        }
        if ($search) {
            $mutasiQuery->whereHas('produk', function ($q) use ($search) {
                $q->where('nama_barang', 'ilike', "%{$search}%")
                    ->orWhere('kode_barang', 'ilike', "%{$search}%");
            });
        }

        $detailMutasi = $mutasiQuery->paginate(100)->withQueryString();

        // ====== Agregasi mutasi ======
        $mutasiAgg = StokRiwayat::query()
            ->whereBetween('stok_riwayat.tanggal', [$dari, $sampai]);
        if ($storeId && $storeId !== 'all') {
            $mutasiAgg->where('stok_riwayat.store_id', $storeId);
        }
        if ($tokoId && $tokoId !== 'all') {
            $mutasiAgg->where('stok_riwayat.store_id', $tokoId);
        }
        if ($produkId && $produkId !== 'all') {
            $mutasiAgg->where('stok_riwayat.produk_id', $produkId);
        }
        $mutasiSummary = $mutasiAgg->selectRaw('
            COUNT(*) as jumlah,
            COALESCE(SUM(CASE WHEN tipe = \'masuk\' THEN qty ELSE 0 END), 0) as total_masuk,
            COALESCE(SUM(CASE WHEN tipe = \'keluar\' THEN qty ELSE 0 END), 0) as total_keluar,
            COALESCE(SUM(CASE WHEN tipe = \'penyesuaian\' THEN qty ELSE 0 END), 0) as total_penyesuaian
        ')->first();

        return Inertia::render('LaporanMutasiStok/Index', [
            'tab' => $tab,
            'kartuStok' => $kartuStok,
            'kartuSummary' => $kartuSummary,
            'detailMutasi' => $detailMutasi,
            'mutasiSummary' => $mutasiSummary,
            'produks' => Produk::orderBy('nama_barang')->get(['id', 'kode_barang', 'nama_barang']),
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
            'filters' => [
                'tab' => $tab,
                'dari' => $dari,
                'sampai' => $sampai,
                'produk_id' => $produkId ?? '',
                'toko_id' => $tokoId ?? '',
                'search' => $search,
            ],
        ]);
    }
}
