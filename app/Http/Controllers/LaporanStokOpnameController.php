<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StokOpname;
use App\Models\StokOpnameDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaporanStokOpnameController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->toDateString());
        $status = $request->input('status');
        $tokoId = $request->input('toko_id');
        $search = trim((string) $request->input('search', ''));

        // ====== Base query factory for opname headers ======
        $baseQuery = function () use ($storeId, $dari, $sampai, $status, $tokoId) {
            $q = StokOpname::query()
                ->whereBetween('stok_opname.tanggal', [$dari, $sampai]);

            if ($storeId && $storeId !== 'all') {
                $q->where('stok_opname.store_id', $storeId);
            }
            if ($tokoId && $tokoId !== 'all') {
                $q->where('stok_opname.store_id', $tokoId);
            }
            if ($status && $status !== 'semua') {
                $q->where('stok_opname.status', $status);
            }

            return $q;
        };

        // ====== Agregasi opname headers ======
        $totals = $baseQuery()->selectRaw('
            COUNT(*) as jumlah_opname,
            COALESCE(SUM(CASE WHEN status = \'selesai\' THEN 1 ELSE 0 END), 0) as selesai,
            COALESCE(SUM(CASE WHEN status = \'draft\' THEN 1 ELSE 0 END), 0) as draft,
            COALESCE(SUM(CASE WHEN status = \'batal\' THEN 1 ELSE 0 END), 0) as batal
        ')->first();

        // ====== Base query factory for detail items ======
        $detailQuery = function () use ($storeId, $dari, $sampai, $status, $tokoId, $search) {
            $q = StokOpnameDetail::query()
                ->whereHas('stokOpname', function ($sq) use ($dari, $sampai, $storeId, $tokoId, $status) {
                    $sq->whereBetween('stok_opname.tanggal', [$dari, $sampai]);
                    if ($storeId && $storeId !== 'all') {
                        $sq->where('stok_opname.store_id', $storeId);
                    }
                    if ($tokoId && $tokoId !== 'all') {
                        $sq->where('stok_opname.store_id', $tokoId);
                    }
                    if ($status && $status !== 'semua') {
                        $sq->where('stok_opname.status', $status);
                    }
                });

            if ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('nama_barang', 'ilike', "%{$search}%")
                        ->orWhereHas('produk', function ($p) use ($search) {
                            $p->where('kode_barang', 'ilike', "%{$search}%");
                        });
                });
            }

            return $q;
        };

        // ====== Agregasi detail items ======
        $detailTotals = $detailQuery()->selectRaw('
            COUNT(*) as jumlah_item,
            COALESCE(SUM(stok_sistem), 0) as total_sistem,
            COALESCE(SUM(stok_fisik), 0) as total_fisik,
            COALESCE(SUM(selisih), 0) as total_selisih,
            COALESCE(SUM(CASE WHEN selisih > 0 THEN selisih ELSE 0 END), 0) as selisih_lebih,
            COALESCE(SUM(CASE WHEN selisih < 0 THEN ABS(selisih) ELSE 0 END), 0) as selisih_kurang
        ')->first();

        // ====== Rekap per Toko ======
        $perToko = $baseQuery()
            ->selectRaw('
                stok_opname.store_id,
                COALESCE(stores.nama, ?) as nama_toko,
                COUNT(*) as jumlah,
                COALESCE(SUM(CASE WHEN stok_opname.status = \'selesai\' THEN 1 ELSE 0 END), 0) as selesai,
                COALESCE(SUM(CASE WHEN stok_opname.status = \'draft\' THEN 1 ELSE 0 END), 0) as draft
            ', ['Pusat'])
            ->leftJoin('stores', 'stok_opname.store_id', '=', 'stores.id')
            ->groupBy('stok_opname.store_id', 'stores.nama')
            ->orderByDesc('jumlah')
            ->get();

        // ====== Detail items (paginated) ======
        $detailItems = $detailQuery()
            ->with(['stokOpname:id,no_opname,tanggal,status,petugas,store_id', 'stokOpname.store:id,nama', 'produk:id,kode_barang,nama_barang'])
            ->orderByDesc('stok_opname_detail.id')
            ->paginate(100)
            ->withQueryString();

        return Inertia::render('LaporanStokOpname/Index', [
            'totals' => $totals,
            'detailTotals' => $detailTotals,
            'perToko' => $perToko,
            'detailItems' => $detailItems,
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
            'filters' => [
                'dari' => $dari,
                'sampai' => $sampai,
                'status' => $status ?? '',
                'toko_id' => $tokoId ?? '',
                'search' => $search,
            ],
        ]);
    }
}
