<?php

namespace App\Http\Controllers;

use App\Models\PembayaranHutang;
use App\Models\Pembelian;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaporanHutangDagangController extends Controller
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
                ->whereBetween('tgl_pembelian', [$dari, $sampai]);

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
            COALESCE(SUM(pembayaran_hutang.nilai_pembelian), 0) as total_nilai,
            COALESCE(SUM(pembayaran_hutang.retur_pembelian), 0) as total_retur,
            COALESCE(SUM(pembayaran_hutang.diskon_pembayaran), 0) as total_diskon,
            COALESCE(SUM(pembayaran_hutang.total_harus_dibayar), 0) as total_harus_dibayar,
            COALESCE(SUM(pembayaran_hutang.jumlah_bayar), 0) as total_jumlah_bayar,
            COALESCE(SUM(pembayaran_hutang.total_terbayar), 0) as total_terbayar,
            COALESCE(SUM(pembayaran_hutang.kurang_bayar), 0) as total_kurang_bayar,
            COALESCE(SUM(pembayaran_hutang.sisa_hutang), 0) as total_sisa_hutang,
            COALESCE(SUM(CASE WHEN pembayaran_hutang.sisa_hutang > 0 THEN 1 ELSE 0 END), 0) as jumlah_belum_lunas
        ')->first();

        // ====== Rekap per Supplier ======
        $perSupplier = $baseQuery()
            ->selectRaw('
                pembayaran_hutang.supplier_id,
                COALESCE(suppliers.nama, ?) as nama_supplier,
                COUNT(*) as jumlah,
                COALESCE(SUM(pembayaran_hutang.total_harus_dibayar), 0) as harus_bayar,
                COALESCE(SUM(pembayaran_hutang.total_terbayar), 0) as terbayar,
                COALESCE(SUM(pembayaran_hutang.kurang_bayar), 0) as kurang_bayar,
                COALESCE(SUM(pembayaran_hutang.sisa_hutang), 0) as sisa_hutang,
                COALESCE(SUM(CASE WHEN pembayaran_hutang.sisa_hutang > 0 THEN 1 ELSE 0 END), 0) as belum_lunas
            ', ['Non Supplier'])
            ->leftJoin('suppliers', 'pembayaran_hutang.supplier_id', '=', 'suppliers.id')
            ->groupBy('pembayaran_hutang.supplier_id', 'suppliers.nama')
            ->orderByDesc('sisa_hutang')
            ->get();

        // ====== Rekap per Toko ======
        $perToko = $baseQuery()
            ->selectRaw('
                pembayaran_hutang.store_id,
                COALESCE(stores.nama, ?) as nama_toko,
                COUNT(*) as jumlah,
                COALESCE(SUM(pembayaran_hutang.total_harus_dibayar), 0) as harus_bayar,
                COALESCE(SUM(pembayaran_hutang.total_terbayar), 0) as terbayar,
                COALESCE(SUM(pembayaran_hutang.sisa_hutang), 0) as sisa_hutang
            ', ['Pusat'])
            ->leftJoin('stores', 'pembayaran_hutang.store_id', '=', 'stores.id')
            ->groupBy('pembayaran_hutang.store_id', 'stores.nama')
            ->orderByDesc('sisa_hutang')
            ->get();

        // ====== Detail transaksi ======
        $detailTransaksi = $baseQuery()
            ->with(['supplier:id,nama', 'store:id,nama'])
            ->select(
                'pembayaran_hutang.*',
            )
            ->orderByDesc('pembayaran_hutang.tgl_jatuh_tempo')
            ->orderByDesc('pembayaran_hutang.id')
            ->paginate(100)
            ->withQueryString();

        // ====== Pembelian kredit yang belum ada di pembayaran_hutang ======
        $hutangKredit = Pembelian::query()
            ->where('jenis_bayar', 'kredit')
            ->where('sisa_hutang', '>', 0)
            ->with(['supplier:id,nama', 'store:id,nama'])
            ->select(
                'pembelian.id',
                'pembelian.no_pembelian',
                'pembelian.no_faktur',
                'pembelian.tanggal',
                'pembelian.nama_supplier',
                'pembelian.total',
                'pembelian.sisa_hutang',
                'pembelian.store_id',
                'pembelian.supplier_id',
            )
            ->when($storeId && $storeId !== 'all', fn ($q) => $q->where('pembelian.store_id', $storeId))
            ->when($tokoId && $tokoId !== 'all', fn ($q) => $q->where('pembelian.store_id', $tokoId))
            ->when($supplierId && $supplierId !== 'all', fn ($q) => $q->where('pembelian.supplier_id', $supplierId))
            ->orderByDesc('pembelian.tanggal')
            ->get();

        $totalHutangKredit = (float) $hutangKredit->sum('sisa_hutang');

        return Inertia::render('LaporanHutangDagang/Index', [
            'totals' => $totals,
            'perSupplier' => $perSupplier,
            'perToko' => $perToko,
            'detailTransaksi' => $detailTransaksi,
            'hutangKredit' => $hutangKredit,
            'totalHutangKredit' => $totalHutangKredit,
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
