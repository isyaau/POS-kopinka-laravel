<?php

namespace App\Http\Controllers;

use App\Models\PembayaranHutang;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaporanPembayaranAngsuranHutangController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->toDateString());
        $supplierId = $request->input('supplier_id');
        $tokoId = $request->input('toko_id');

        // ====== Base query factory ======
        $baseQuery = function () use ($storeId, $dari, $sampai, $supplierId, $tokoId) {
            $q = PembayaranHutang::query()
                ->whereBetween('pembayaran_hutang.tanggal_bayar', [$dari, $sampai]);

            if ($storeId && $storeId !== 'all') {
                $q->where('pembayaran_hutang.store_id', $storeId);
            }
            if ($tokoId && $tokoId !== 'all') {
                $q->where('pembayaran_hutang.store_id', $tokoId);
            }
            if ($supplierId && $supplierId !== 'all') {
                $q->where('pembayaran_hutang.supplier_id', $supplierId);
            }

            return $q;
        };

        // ====== Agregasi total ======
        $totals = $baseQuery()->selectRaw('
            COUNT(*) as jumlah_transaksi,
            COALESCE(SUM(nilai_pembelian), 0) as total_nilai,
            COALESCE(SUM(total_harus_dibayar), 0) as total_harus_bayar,
            COALESCE(SUM(jumlah_bayar), 0) as total_jumlah_bayar,
            COALESCE(SUM(total_terbayar), 0) as total_terbayar,
            COALESCE(SUM(total_diskon), 0) as total_diskon,
            COALESCE(SUM(kurang_bayar), 0) as total_kurang_bayar,
            COALESCE(SUM(sisa_hutang), 0) as total_sisa_hutang,
            COALESCE(SUM(CASE WHEN sisa_hutang > 0 THEN 1 ELSE 0 END), 0) as jumlah_belum_lunas,
            COALESCE(SUM(CASE WHEN sisa_hutang <= 0 THEN 1 ELSE 0 END), 0) as jumlah_lunas
        ')->first();

        // ====== Rekap per Supplier ======
        $perSupplier = $baseQuery()
            ->selectRaw('
                pembayaran_hutang.supplier_id,
                COALESCE(suppliers.nama, ?) as nama_supplier,
                COUNT(*) as jumlah,
                COALESCE(SUM(total_harus_dibayar), 0) as harus_bayar,
                COALESCE(SUM(jumlah_bayar), 0) as jumlah_bayar,
                COALESCE(SUM(total_terbayar), 0) as terbayar,
                COALESCE(SUM(sisa_hutang), 0) as sisa_hutang
            ', ['Non Supplier'])
            ->leftJoin('suppliers', 'pembayaran_hutang.supplier_id', '=', 'suppliers.id')
            ->groupBy('pembayaran_hutang.supplier_id', 'suppliers.nama')
            ->orderByDesc('terbayar')
            ->get();

        // ====== Rekap per Toko ======
        $perToko = $baseQuery()
            ->selectRaw('
                pembayaran_hutang.store_id,
                COALESCE(stores.nama, ?) as nama_toko,
                COUNT(*) as jumlah,
                COALESCE(SUM(jumlah_bayar), 0) as jumlah_bayar,
                COALESCE(SUM(total_terbayar), 0) as terbayar,
                COALESCE(SUM(sisa_hutang), 0) as sisa_hutang
            ', ['Pusat'])
            ->leftJoin('stores', 'pembayaran_hutang.store_id', '=', 'stores.id')
            ->groupBy('pembayaran_hutang.store_id', 'stores.nama')
            ->orderByDesc('terbayar')
            ->get();

        // ====== Rekap per Bulan ======
        $perBulan = $baseQuery()
            ->selectRaw("
                TO_CHAR(pembayaran_hutang.tanggal_bayar, 'YYYY-MM') as bulan,
                COUNT(*) as jumlah,
                COALESCE(SUM(jumlah_bayar), 0) as jumlah_bayar,
                COALESCE(SUM(total_terbayar), 0) as terbayar,
                COALESCE(SUM(total_diskon), 0) as diskon
            ")
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        // ====== Detail transaksi (paginated) ======
        $detailTransaksi = $baseQuery()
            ->with(['supplier:id,nama', 'store:id,nama'])
            ->select(
                'pembayaran_hutang.*',
            )
            ->orderByDesc('pembayaran_hutang.tanggal_bayar')
            ->orderByDesc('pembayaran_hutang.id')
            ->paginate(100)
            ->withQueryString();

        return Inertia::render('LaporanPembayaranAngsuranHutang/Index', [
            'totals' => $totals,
            'perSupplier' => $perSupplier,
            'perToko' => $perToko,
            'perBulan' => $perBulan,
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
