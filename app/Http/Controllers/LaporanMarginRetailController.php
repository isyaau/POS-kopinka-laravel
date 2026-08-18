<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LaporanMarginRetailController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = session('store_id');
        $kategori = $request->input('kategori');
        $search = trim((string) $request->input('search', ''));

        // ====== Shared subquery for stok aggregation ======
        $stokSubQuery = function ($q) use ($storeId) {
            $q->selectRaw('produk_id, SUM(stok) as total_stok')
                ->from('stok')
                ->when($storeId && $storeId !== 'all', function ($sq) use ($storeId) {
                    $sq->where('store_id', $storeId);
                })
                ->groupBy('produk_id');
        };

        // ====== Filter conditions builder ======
        $applyFilters = function ($q) use ($kategori, $search) {
            if ($kategori && $kategori !== 'semua') {
                $q->where('produk.kategori', $kategori);
            }
            if ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('produk.nama_barang', 'ilike', "%{$search}%")
                        ->orWhere('produk.kode_barang', 'ilike', "%{$search}%");
                });
            }
        };

        // ====== Summary: use raw expressions, not aliases ======
        $totals = DB::table('produk')
            ->leftJoinSub($stokSubQuery, 'stok_agg', 'produk.id', '=', 'stok_agg.produk_id')
            ->where('produk.deleted_at', null)
            ->tap($applyFilters)
            ->selectRaw('
                COUNT(*) as jumlah_produk,
                COALESCE(SUM(stok_agg.total_stok), 0) as total_stok,
                COALESCE(SUM(COALESCE(stok_agg.total_stok, 0) * (produk.harga_jual - produk.harga_beli)), 0) as total_margin_stok,
                COALESCE(SUM(COALESCE(stok_agg.total_stok, 0) * produk.harga_beli), 0) as total_modal_stok,
                COALESCE(SUM(COALESCE(stok_agg.total_stok, 0) * produk.harga_jual), 0) as total_nilai_stok,
                COALESCE(AVG(CASE WHEN produk.harga_beli > 0 THEN ROUND(((produk.harga_jual - produk.harga_beli) / produk.harga_beli * 100), 2) ELSE 0 END), 0) as avg_margin_persen,
                COALESCE(MAX(CASE WHEN produk.harga_beli > 0 THEN ROUND(((produk.harga_jual - produk.harga_beli) / produk.harga_beli * 100), 2) ELSE 0 END), 0) as max_margin_persen,
                COALESCE(MIN(CASE WHEN produk.harga_beli > 0 THEN ROUND(((produk.harga_jual - produk.harga_beli) / produk.harga_beli * 100), 2) ELSE 0 END), 0) as min_margin_persen,
                COALESCE(SUM(CASE WHEN produk.harga_jual <= produk.harga_beli THEN 1 ELSE 0 END), 0) as rugi_count,
                COALESCE(SUM(CASE WHEN produk.harga_jual > produk.harga_beli AND CASE WHEN produk.harga_beli > 0 THEN ((produk.harga_jual - produk.harga_beli) / produk.harga_beli * 100) ELSE 0 END < 10 THEN 1 ELSE 0 END), 0) as rendah_count,
                COALESCE(SUM(CASE WHEN produk.harga_jual > produk.harga_beli AND CASE WHEN produk.harga_beli > 0 THEN ((produk.harga_jual - produk.harga_beli) / produk.harga_beli * 100) ELSE 0 END >= 10 AND CASE WHEN produk.harga_beli > 0 THEN ((produk.harga_jual - produk.harga_beli) / produk.harga_beli * 100) ELSE 0 END < 25 THEN 1 ELSE 0 END), 0) as sedang_count,
                COALESCE(SUM(CASE WHEN CASE WHEN produk.harga_beli > 0 THEN ((produk.harga_jual - produk.harga_beli) / produk.harga_beli * 100) ELSE 0 END >= 25 THEN 1 ELSE 0 END), 0) as tinggi_count
            ')->first();

        // ====== Rekap per Kategori ======
        $perKategori = DB::table('produk')
            ->leftJoinSub($stokSubQuery, 'stok_agg', 'produk.id', '=', 'stok_agg.produk_id')
            ->where('produk.deleted_at', null)
            ->tap($applyFilters)
            ->selectRaw('
                produk.kategori,
                COUNT(*) as jumlah,
                COALESCE(AVG(CASE WHEN produk.harga_beli > 0 THEN ROUND(((produk.harga_jual - produk.harga_beli) / produk.harga_beli * 100), 2) ELSE 0 END), 0) as avg_margin,
                COALESCE(SUM(COALESCE(stok_agg.total_stok, 0) * (produk.harga_jual - produk.harga_beli)), 0) as total_margin,
                COALESCE(SUM(COALESCE(stok_agg.total_stok, 0) * produk.harga_beli), 0) as total_modal,
                COALESCE(SUM(COALESCE(stok_agg.total_stok, 0)), 0) as total_stok
            ')
            ->groupBy('produk.kategori')
            ->orderByDesc('total_margin')
            ->get();

        // ====== Detail produk (top 200 by margin) ======
        $detailProduk = DB::table('produk')
            ->leftJoinSub($stokSubQuery, 'stok_agg', 'produk.id', '=', 'stok_agg.produk_id')
            ->where('produk.deleted_at', null)
            ->tap($applyFilters)
            ->selectRaw('
                produk.id,
                produk.kode_barang,
                produk.nama_barang,
                produk.kategori,
                produk.satuan,
                produk.harga_beli,
                produk.harga_jual,
                (produk.harga_jual - produk.harga_beli) as margin_rupiah,
                CASE WHEN produk.harga_beli > 0 THEN ROUND(((produk.harga_jual - produk.harga_beli) / produk.harga_beli * 100), 2) ELSE 0 END as margin_persen,
                COALESCE(stok_agg.total_stok, 0) as total_stok,
                ROUND(COALESCE(stok_agg.total_stok, 0) * (produk.harga_jual - produk.harga_beli), 2) as total_margin_stok,
                COALESCE(stok_agg.total_stok, 0) * produk.harga_beli as modal_stok,
                COALESCE(stok_agg.total_stok, 0) * produk.harga_jual as nilai_stok
            ')
            ->orderByDesc('margin_persen')
            ->limit(200)
            ->get();

        // ====== Kategoris for filter ======
        $kategoris = DB::table('produk')
            ->where('deleted_at', null)
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->distinct()
            ->orderBy('kategori')
            ->pluck('kategori');

        return Inertia::render('LaporanMarginRetail/Index', [
            'totals' => $totals,
            'perKategori' => $perKategori,
            'detailProduk' => $detailProduk,
            'kategoris' => $kategoris,
            'filters' => [
                'kategori' => $kategori ?? '',
                'search' => $search,
            ],
        ]);
    }
}
