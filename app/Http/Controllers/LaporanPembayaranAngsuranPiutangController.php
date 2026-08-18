<?php

namespace App\Http\Controllers;

use App\Models\PenerimaanAngsuran;
use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaporanPembayaranAngsuranPiutangController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->toDateString());
        $tokoId = $request->input('toko_id');

        // ====== Base query factory ======
        $baseQuery = function () use ($storeId, $dari, $sampai, $tokoId) {
            $q = PenerimaanAngsuran::query()
                ->whereBetween('penerimaan_angsuran.tgl_transaksi', [$dari, $sampai]);

            if ($storeId && $storeId !== 'all') {
                $q->where('penerimaan_angsuran.store_id', $storeId);
            }
            if ($tokoId && $tokoId !== 'all') {
                $q->where('penerimaan_angsuran.store_id', $tokoId);
            }

            return $q;
        };

        // ====== Agregasi total ======
        $totals = $baseQuery()->selectRaw('
            COUNT(*) as jumlah_transaksi,
            COALESCE(SUM(nilai_piutang), 0) as total_piutang,
            COALESCE(SUM(retur_penjualan), 0) as total_retur,
            COALESCE(SUM(diskon_pembayaran), 0) as total_diskon,
            COALESCE(SUM(total_harus_dibayar), 0) as total_harus_bayar,
            COALESCE(SUM(jumlah_bayar), 0) as total_jumlah_bayar,
            COALESCE(SUM(total_terbayar), 0) as total_terbayar,
            COALESCE(SUM(total_diskon), 0) as total_diskon_all,
            COALESCE(SUM(kurang_bayar), 0) as total_kurang_bayar,
            COALESCE(SUM(sisa_piutang), 0) as total_sisa_piutang,
            COALESCE(SUM(CASE WHEN sisa_piutang > 0 THEN 1 ELSE 0 END), 0) as jumlah_belum_lunas,
            COALESCE(SUM(CASE WHEN sisa_piutang <= 0 THEN 1 ELSE 0 END), 0) as jumlah_lunas
        ')->first();

        // ====== Rekap per Anggota ======
        $perAnggota = $baseQuery()
            ->selectRaw('
                penerimaan_angsuran.anggota_id,
                COALESCE(anggota.nama, ?) as nama_anggota,
                COUNT(*) as jumlah,
                COALESCE(SUM(total_harus_dibayar), 0) as harus_bayar,
                COALESCE(SUM(total_terbayar), 0) as terbayar,
                COALESCE(SUM(sisa_piutang), 0) as sisa_piutang
            ', ['Non Anggota'])
            ->leftJoin('anggota', 'penerimaan_angsuran.anggota_id', '=', 'anggota.id')
            ->groupBy('penerimaan_angsuran.anggota_id', 'anggota.nama')
            ->orderByDesc('terbayar')
            ->get();

        // ====== Rekap per Toko ======
        $perToko = $baseQuery()
            ->selectRaw('
                penerimaan_angsuran.store_id,
                COALESCE(stores.nama, ?) as nama_toko,
                COUNT(*) as jumlah,
                COALESCE(SUM(jumlah_bayar), 0) as jumlah_bayar,
                COALESCE(SUM(total_terbayar), 0) as terbayar,
                COALESCE(SUM(sisa_piutang), 0) as sisa_piutang
            ', ['Pusat'])
            ->leftJoin('stores', 'penerimaan_angsuran.store_id', '=', 'stores.id')
            ->groupBy('penerimaan_angsuran.store_id', 'stores.nama')
            ->orderByDesc('terbayar')
            ->get();

        // ====== Rekap per Bulan ======
        $perBulan = $baseQuery()
            ->selectRaw("
                TO_CHAR(penerimaan_angsuran.tgl_transaksi, 'YYYY-MM') as bulan,
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
            ->with(['anggota:id,nama,nip', 'store:id,nama'])
            ->orderByDesc('penerimaan_angsuran.tgl_transaksi')
            ->orderByDesc('penerimaan_angsuran.id')
            ->paginate(100)
            ->withQueryString();

        return Inertia::render('LaporanPembayaranAngsuranPiutang/Index', [
            'totals' => $totals,
            'perAnggota' => $perAnggota,
            'perToko' => $perToko,
            'perBulan' => $perBulan,
            'detailTransaksi' => $detailTransaksi,
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
            'filters' => [
                'dari' => $dari,
                'sampai' => $sampai,
                'toko_id' => $tokoId ?? '',
            ],
        ]);
    }
}
