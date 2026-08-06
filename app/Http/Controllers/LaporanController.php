<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaporanController extends Controller
{
    /**
     * Laporan penjualan per periode (default: bulan berjalan).
     * Jika store_id = all / kosong, tampilkan rekap per toko.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        // Periode
        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->toDateString());
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) {
            $dari = now()->startOfMonth()->toDateString();
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) {
            $sampai = now()->toDateString();
        }
        if ($sampai < $dari) {
            $sampai = $dari;
        }

        $query = Transaksi::query()
            ->with('store')
            ->whereBetween('tanggal', [$dari, $sampai]);

        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        $transaksi = $query->orderByDesc('tanggal')->get();

        // Rekap per toko (selalu dihitung dari data transaksi periode tsb)
        $perToko = $transaksi
            ->groupBy(fn ($t) => $t->store_id ?? 0)
            ->map(function ($rows, $sid) {
                $store = $rows->first()->store;

                return [
                    'store_id' => $sid,
                    'nama_toko' => $store?->nama ?? 'Tanpa Toko',
                    'kode_toko' => $store?->kode ?? '-',
                    'jumlah' => $rows->count(),
                    'omzet' => (float) $rows->sum('jual'),
                    'cash' => (float) $rows->sum('cash'),
                    'qris' => (float) $rows->sum('qris'),
                    'edc' => (float) $rows->sum('edc'),
                    'voucher' => (float) $rows->sum('voucher'),
                    'piutang' => (float) $rows->sum('piutang'),
                ];
            })
            ->values()
            ->toArray();

        // Rekap produk terjual periode (untuk detail)
        $detailIds = $transaksi->pluck('id');
        $perProduk = TransaksiDetail::query()
            ->whereIn('transaksi_id', $detailIds)
            ->selectRaw('nama_barang, SUM(qty) AS qty, SUM(subtotal) AS total')
            ->groupBy('nama_barang')
            ->orderByDesc('qty')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'nama_barang' => $r->nama_barang,
                'qty' => (int) $r->qty,
                'total' => (float) $r->total,
            ])
            ->toArray();

        // Ringkasan total
        $total = [
            'jumlah' => $transaksi->count(),
            'omzet' => (float) $transaksi->sum('jual'),
            'cash' => (float) $transaksi->sum('cash'),
            'qris' => (float) $transaksi->sum('qris'),
            'edc' => (float) $transaksi->sum('edc'),
            'voucher' => (float) $transaksi->sum('voucher'),
            'piutang' => (float) $transaksi->sum('piutang'),
        ];

        return Inertia::render('Laporan/Index', [
            'dari' => $dari,
            'sampai' => $sampai,
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
            'perToko' => $perToko,
            'perProduk' => $perProduk,
            'transaksi' => $transaksi
                ->map(fn ($t) => [
                    'no_nota' => $t->no_nota,
                    'tanggal' => $t->tanggal?->toDateString(),
                    'nama_anggota' => $t->nama_anggota,
                    'nama_toko' => $t->store?->nama ?? '-',
                    'jual' => (float) $t->jual,
                    'cash' => (float) $t->cash,
                    'qris' => (float) $t->qris,
                    'edc' => (float) $t->edc,
                    'voucher' => (float) $t->voucher,
                    'piutang' => (float) $t->piutang,
                ])
                ->values()
                ->toArray(),
            'total' => $total,
        ]);
    }
}
