<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\PenerimaanAngsuran;
use App\Models\Store;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaporanPiutangAnggotaController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->toDateString());
        $anggotaId = $request->input('anggota_id');
        $tokoId = $request->input('toko_id');

        // ====== Base query factory for transaksi with piutang ======
        $transaksiQuery = function () use ($storeId, $dari, $sampai, $anggotaId, $tokoId) {
            $q = Transaksi::query()
                ->where('piutang', '>', 0)
                ->whereBetween('tanggal', [$dari, $sampai]);

            if ($storeId && $storeId !== 'all') {
                $q->where('transaksi.store_id', $storeId);
            }
            if ($tokoId && $tokoId !== 'all') {
                $q->where('transaksi.store_id', $tokoId);
            }
            if ($anggotaId && $anggotaId !== 'all') {
                $q->where('transaksi.anggota_id', $anggotaId);
            }

            return $q;
        };

        // ====== Base query factory for angsuran ======
        $angsuranQuery = function () use ($storeId, $dari, $sampai, $anggotaId, $tokoId) {
            $q = PenerimaanAngsuran::query()
                ->whereBetween('tgl_transaksi', [$dari, $sampai]);

            if ($storeId && $storeId !== 'all') {
                $q->where('penerimaan_angsuran.store_id', $storeId);
            }
            if ($tokoId && $tokoId !== 'all') {
                $q->where('penerimaan_angsuran.store_id', $tokoId);
            }
            if ($anggotaId && $anggotaId !== 'all') {
                $q->where('penerimaan_angsuran.anggota_id', $anggotaId);
            }

            return $q;
        };

        // ====== Agregasi transaksi ======
        $transaksiTotals = $transaksiQuery()->selectRaw('
            COUNT(*) as jumlah,
            COALESCE(SUM(transaksi.jual), 0) as total_penjualan,
            COALESCE(SUM(transaksi.piutang), 0) as total_piutang
        ')->first();

        // ====== Agregasi angsuran ======
        $angsuranTotals = $angsuranQuery()->selectRaw('
            COUNT(*) as jumlah,
            COALESCE(SUM(penerimaan_angsuran.total_harus_dibayar), 0) as harus_bayar,
            COALESCE(SUM(penerimaan_angsuran.total_terbayar), 0) as total_terbayar,
            COALESCE(SUM(penerimaan_angsuran.kurang_bayar), 0) as kurang_bayar,
            COALESCE(SUM(penerimaan_angsuran.sisa_piutang), 0) as sisa_piutang,
            COALESCE(SUM(CASE WHEN penerimaan_angsuran.sisa_piutang > 0 THEN 1 ELSE 0 END), 0) as jumlah_belum_lunas
        ')->first();

        // ====== Rekap per Anggota ======
        $perAnggota = $transaksiQuery()
            ->selectRaw('
                transaksi.anggota_id,
                COALESCE(anggota.nama, ?) as nama_anggota,
                COUNT(*) as jumlah,
                COALESCE(SUM(transaksi.jual), 0) as total_penjualan,
                COALESCE(SUM(transaksi.piutang), 0) as total_piutang
            ', ['Non Anggota'])
            ->leftJoin('anggota', 'transaksi.anggota_id', '=', 'anggota.id')
            ->groupBy('transaksi.anggota_id', 'anggota.nama')
            ->orderByDesc('total_piutang')
            ->get();

        // ====== Rekap per Toko ======
        $perToko = $transaksiQuery()
            ->selectRaw('
                transaksi.store_id,
                COALESCE(stores.nama, ?) as nama_toko,
                COUNT(*) as jumlah,
                COALESCE(SUM(transaksi.jual), 0) as total_penjualan,
                COALESCE(SUM(transaksi.piutang), 0) as total_piutang
            ', ['Pusat'])
            ->leftJoin('stores', 'transaksi.store_id', '=', 'stores.id')
            ->groupBy('transaksi.store_id', 'stores.nama')
            ->orderByDesc('total_piutang')
            ->get();

        // ====== Detail transaksi piutang ======
        $detailTransaksi = $transaksiQuery()
            ->with(['anggota:id,nama,nip', 'store:id,nama'])
            ->select(
                'transaksi.id',
                'transaksi.no_nota',
                'transaksi.tanggal',
                'transaksi.anggota_id',
                'transaksi.nama_anggota',
                'transaksi.jual',
                'transaksi.piutang',
                'transaksi.store_id',
            )
            ->orderByDesc('transaksi.tanggal')
            ->orderByDesc('transaksi.id')
            ->paginate(100)
            ->withQueryString();

        // ====== Detail angsuran ======
        $detailAngsuran = $angsuranQuery()
            ->with(['anggota:id,nama', 'store:id,nama'])
            ->select(
                'penerimaan_angsuran.*',
            )
            ->orderByDesc('penerimaan_angsuran.tgl_transaksi')
            ->orderByDesc('penerimaan_angsuran.id')
            ->paginate(100)
            ->withQueryString();

        return Inertia::render('LaporanPiutangAnggota/Index', [
            'transaksiTotals' => $transaksiTotals,
            'angsuranTotals' => $angsuranTotals,
            'perAnggota' => $perAnggota,
            'perToko' => $perToko,
            'detailTransaksi' => $detailTransaksi,
            'detailAngsuran' => $detailAngsuran,
            'anggotas' => Anggota::orderBy('nama')->get(['id', 'nip', 'nama']),
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
            'filters' => [
                'dari' => $dari,
                'sampai' => $sampai,
                'anggota_id' => $anggotaId ?? '',
                'toko_id' => $tokoId ?? '',
            ],
        ]);
    }
}
