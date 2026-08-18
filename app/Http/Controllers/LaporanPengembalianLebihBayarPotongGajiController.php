<?php

namespace App\Http\Controllers;

use App\Models\PengembalianLebihBayarPotongGaji;
use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaporanPengembalianLebihBayarPotongGajiController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->toDateString());
        $tokoId = $request->input('toko_id');
        $status = $request->input('status');

        // ====== Base query factory ======
        $baseQuery = function () use ($storeId, $dari, $sampai, $tokoId, $status) {
            $q = PengembalianLebihBayarPotongGaji::query()
                ->whereBetween('pengembalian_lebih_bayar_potong_gaji.tgl_pengembalian', [$dari, $sampai]);

            if ($storeId && $storeId !== 'all') {
                $q->where('pengembalian_lebih_bayar_potong_gaji.store_id', $storeId);
            }
            if ($tokoId && $tokoId !== 'all') {
                $q->where('pengembalian_lebih_bayar_potong_gaji.store_id', $tokoId);
            }
            if ($status && $status !== 'semua') {
                $q->where('pengembalian_lebih_bayar_potong_gaji.status', $status);
            }

            return $q;
        };

        // ====== Agregasi total ======
        $totals = $baseQuery()->selectRaw('
            COUNT(*) as jumlah_transaksi,
            COALESCE(SUM(jumlah_lebih_bayar), 0) as total_lebih_bayar,
            COALESCE(SUM(jumlah_pengembalian), 0) as total_pengembalian,
            COALESCE(SUM(CASE WHEN status = \'selesai\' THEN 1 ELSE 0 END), 0) as selesai,
            COALESCE(SUM(CASE WHEN status = \'pending\' THEN 1 ELSE 0 END), 0) as pending,
            COALESCE(SUM(CASE WHEN status = \'dibatalkan\' THEN 1 ELSE 0 END), 0) as dibatalkan
        ')->first();

        // ====== Rekap per Anggota ======
        $perAnggota = $baseQuery()
            ->selectRaw('
                pengembalian_lebih_bayar_potong_gaji.anggota_id,
                COALESCE(anggota.nama, ?) as nama_anggota,
                COUNT(*) as jumlah,
                COALESCE(SUM(jumlah_lebih_bayar), 0) as total_lebih_bayar,
                COALESCE(SUM(jumlah_pengembalian), 0) as total_pengembalian
            ', ['Non Anggota'])
            ->leftJoin('anggota', 'pengembalian_lebih_bayar_potong_gaji.anggota_id', '=', 'anggota.id')
            ->groupBy('pengembalian_lebih_bayar_potong_gaji.anggota_id', 'anggota.nama')
            ->orderByDesc('total_pengembalian')
            ->get();

        // ====== Rekap per Toko ======
        $perToko = $baseQuery()
            ->selectRaw('
                pengembalian_lebih_bayar_potong_gaji.store_id,
                COALESCE(stores.nama, ?) as nama_toko,
                COUNT(*) as jumlah,
                COALESCE(SUM(jumlah_lebih_bayar), 0) as total_lebih_bayar,
                COALESCE(SUM(jumlah_pengembalian), 0) as total_pengembalian
            ', ['Pusat'])
            ->leftJoin('stores', 'pengembalian_lebih_bayar_potong_gaji.store_id', '=', 'stores.id')
            ->groupBy('pengembalian_lebih_bayar_potong_gaji.store_id', 'stores.nama')
            ->orderByDesc('total_pengembalian')
            ->get();

        // ====== Detail transaksi (paginated) ======
        $detailTransaksi = $baseQuery()
            ->with(['anggota:id,nama,nip', 'store:id,nama'])
            ->orderByDesc('pengembalian_lebih_bayar_potong_gaji.tgl_pengembalian')
            ->orderByDesc('pengembalian_lebih_bayar_potong_gaji.id')
            ->paginate(100)
            ->withQueryString();

        return Inertia::render('LaporanPengembalianLebihBayarPotongGaji/Index', [
            'totals' => $totals,
            'perAnggota' => $perAnggota,
            'perToko' => $perToko,
            'detailTransaksi' => $detailTransaksi,
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
            'filters' => [
                'dari' => $dari,
                'sampai' => $sampai,
                'toko_id' => $tokoId ?? '',
                'status' => $status ?? '',
            ],
        ]);
    }
}
