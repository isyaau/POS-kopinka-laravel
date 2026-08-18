<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaporanPembelianController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->toDateString());
        $supplierId = $request->input('supplier_id');
        $tokoId = $request->input('toko_id');

        // ====== Base query factory (fresh instance each call — no stale clone baggage) ======
        $baseQuery = function () use ($storeId, $dari, $sampai, $supplierId, $tokoId) {
            $q = Pembelian::query()
                ->whereBetween('tanggal', [$dari, $sampai]);

            if ($storeId && $storeId !== 'all') {
                $q->where('pembelian.store_id', $storeId);
            }

            if ($tokoId && $tokoId !== 'all') {
                $q->where('pembelian.store_id', $tokoId);
            }

            if ($supplierId && $supplierId !== 'all') {
                $q->where('pembelian.supplier_id', $supplierId);
            }

            return $q;
        };

        // ====== Agregasi total ======
        $totals = $baseQuery()->selectRaw('
            COUNT(*) as jumlah_transaksi,
            COALESCE(SUM(pembelian.nilai), 0) as total_nilai,
            COALESCE(SUM(pembelian.diskon), 0) as total_diskon,
            COALESCE(SUM(pembelian.subtotal), 0) as total_subtotal,
            COALESCE(SUM(pembelian.ppn_masukan), 0) as total_ppn,
            COALESCE(SUM(pembelian.total), 0) as total_pembelian,
            COALESCE(SUM(CASE WHEN pembelian.jenis_bayar = ? THEN pembelian.sisa_hutang ELSE 0 END), 0) as total_sisa_hutang,
            COALESCE(SUM(CASE WHEN pembelian.jenis_bayar = ? THEN pembelian.total ELSE 0 END), 0) as total_tunai,
            COALESCE(SUM(CASE WHEN pembelian.jenis_bayar = ? THEN pembelian.total ELSE 0 END), 0) as total_kredit,
            COALESCE(SUM(CASE WHEN pembelian.status = ? THEN pembelian.total ELSE 0 END), 0) as total_selesai,
            COALESCE(SUM(CASE WHEN pembelian.status = ? THEN pembelian.total ELSE 0 END), 0) as total_proses,
            COALESCE(SUM(CASE WHEN pembelian.status = ? THEN pembelian.total ELSE 0 END), 0) as total_batal
        ', ['kredit', 'tunai', 'kredit', 'selesai', 'proses', 'batal'])->first();

        // ====== Rekap per Supplier ======
        $perSupplier = $baseQuery()
            ->selectRaw('
                pembelian.supplier_id,
                COALESCE(suppliers.nama, ?) as nama_supplier,
                COUNT(*) as jumlah,
                COALESCE(SUM(pembelian.nilai), 0) as nilai,
                COALESCE(SUM(pembelian.diskon), 0) as diskon,
                COALESCE(SUM(pembelian.total), 0) as total
            ', ['Non Supplier'])
            ->leftJoin('suppliers', 'pembelian.supplier_id', '=', 'suppliers.id')
            ->groupBy('pembelian.supplier_id', 'suppliers.nama')
            ->orderByDesc('total')
            ->get();

        // ====== Rekap per Toko ======
        $perToko = $baseQuery()
            ->selectRaw('
                pembelian.store_id,
                COALESCE(stores.nama, ?) as nama_toko,
                COUNT(*) as jumlah,
                COALESCE(SUM(pembelian.nilai), 0) as nilai,
                COALESCE(SUM(pembelian.diskon), 0) as diskon,
                COALESCE(SUM(pembelian.total), 0) as total
            ', ['Pusat'])
            ->leftJoin('stores', 'pembelian.store_id', '=', 'stores.id')
            ->groupBy('pembelian.store_id', 'stores.nama')
            ->orderByDesc('total')
            ->get();

        // ====== Detail transaksi ======
        $detailTransaksi = $baseQuery()
            ->with(['supplier:id,nama', 'store:id,nama'])
            ->select(
                'pembelian.id',
                'pembelian.no_pembelian',
                'pembelian.no_faktur',
                'pembelian.tanggal',
                'pembelian.nama_supplier',
                'pembelian.nilai',
                'pembelian.diskon',
                'pembelian.subtotal',
                'pembelian.ppn_masukan',
                'pembelian.total',
                'pembelian.jenis_bayar',
                'pembelian.sisa_hutang',
                'pembelian.status',
            )
            ->orderByDesc('pembelian.tanggal')
            ->orderByDesc('pembelian.id')
            ->paginate(100)
            ->withQueryString();

        return Inertia::render('LaporanPembelian/Index', [
            'totals' => $totals,
            'perSupplier' => $perSupplier,
            'perToko' => $perToko,
            'detailTransaksi' => $detailTransaksi,
            'suppliers' => Supplier::orderBy('nama')->get(['id', 'kode', 'nama']),
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
            'filters' => [
                'dari' => $dari,
                'sampai' => $sampai,
                'supplier_id' => $supplierId ?? '',
                'toko_id' => $tokoId ?? '',
            ],
        ]);
    }
}
