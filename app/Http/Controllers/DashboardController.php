<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Produk;
use App\Models\Store;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $storeId = session('store_id');

        $store = null;
        if ($storeId && $storeId !== 'all') {
            $store = Store::find($storeId);
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'current_store' => $store ? [
                    'id' => $store->id,
                    'kode' => $store->kode,
                    'nama' => $store->nama,
                    'tipe' => $store->tipe,
                ] : ($storeId === 'all' ? [
                    'id' => 'all',
                    'kode' => 'ALL',
                    'nama' => 'Semua Toko',
                    'tipe' => 'pusat',
                ] : null),
                'total_stores' => Store::where('is_active', true)->count(),
                'user_role' => $user->getRoleNames()->first(),
                'anggota_stats' => $this->anggotaStats($storeId),
                'penjualan' => $this->penjualanStats($storeId),
                'produk_terlaris' => $this->produkTerlaris($storeId),
                'stok_menipis' => $this->stokMenipis($storeId),
            ],
        ]);
    }

    /**
     * Statistik penjualan: omzet & jumlah transaksi hari ini, serta 7 hari terakhir.
     */
    protected function penjualanStats(?string $storeId): array
    {
        $query = fn () => Transaksi::query()->when($storeId && $storeId !== 'all', fn ($q) => $q->where('store_id', $storeId));

        $hariIni = $query()
            ->whereDate('tanggal', now()->toDateString())
            ->selectRaw('COUNT(*) AS jumlah, COALESCE(SUM(jual), 0) AS omzet')
            ->first();

        $bulanIni = $query()
            ->whereBetween('tanggal', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->selectRaw('COUNT(*) AS jumlah, COALESCE(SUM(jual), 0) AS omzet')
            ->first();

        // 7 hari terakhir (termasuk hari ini)
        $rows = $query()
            ->whereBetween('tanggal', [now()->subDays(6)->toDateString(), now()->toDateString()])
            ->selectRaw('tanggal, COUNT(*) AS jumlah, COALESCE(SUM(jual), 0) AS omzet')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $grafik = [];
        for ($i = 6; $i >= 0; $i--) {
            $tgl = now()->subDays($i)->toDateString();
            $d = $rows->get($tgl);
            $grafik[] = [
                'tanggal' => $tgl,
                'label' => now()->subDays($i)->format('d/m'),
                'jumlah' => (int) ($d->jumlah ?? 0),
                'omzet' => (float) ($d->omzet ?? 0),
            ];
        }

        // Ringkasan metode pembayaran (hari ini)
        $metode = $query()
            ->whereDate('tanggal', now()->toDateString())
            ->selectRaw(
                'COALESCE(SUM(cash), 0) AS cash, COALESCE(SUM(qris), 0) AS qris, COALESCE(SUM(edc), 0) AS edc, COALESCE(SUM(voucher), 0) AS voucher, COALESCE(SUM(piutang), 0) AS piutang'
            )
            ->first();

        return [
            'hari_ini' => [
                'jumlah' => (int) ($hariIni->jumlah ?? 0),
                'omzet' => (float) ($hariIni->omzet ?? 0),
            ],
            'bulan_ini' => [
                'jumlah' => (int) ($bulanIni->jumlah ?? 0),
                'omzet' => (float) ($bulanIni->omzet ?? 0),
            ],
            'grafik' => $grafik,
            'metode' => [
                'cash' => (float) ($metode->cash ?? 0),
                'qris' => (float) ($metode->qris ?? 0),
                'edc' => (float) ($metode->edc ?? 0),
                'voucher' => (float) ($metode->voucher ?? 0),
                'piutang' => (float) ($metode->piutang ?? 0),
            ],
        ];
    }

    /**
     * 5 produk terlaris berdasarkan qty terjual (dari detail transaksi).
     */
    protected function produkTerlaris(?string $storeId): array
    {
        return Transaksi::query()
            ->when($storeId && $storeId !== 'all', fn ($q) => $q->where('store_id', $storeId))
            ->join('transaksi_detail', 'transaksi_detail.transaksi_id', '=', 'transaksi.id')
            ->selectRaw('transaksi_detail.nama_barang, SUM(transaksi_detail.qty) AS qty, SUM(transaksi_detail.subtotal) AS total')
            ->groupBy('transaksi_detail.nama_barang')
            ->orderByDesc('qty')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'nama_barang' => $r->nama_barang,
                'qty' => (int) $r->qty,
                'total' => (float) $r->total,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Produk dengan stok menipis (stok <= stok_minimum), maksimal 5.
     */
    protected function stokMenipis(?string $storeId): array
    {
        return Produk::query()
            ->when($storeId && $storeId !== 'all', fn ($q) => $q->where('store_id', $storeId))
            ->whereColumn('stok', '<=', 'stok_minimum')
            ->orderByRaw('(stok - stok_minimum) ASC')
            ->limit(5)
            ->get(['kode_barang', 'nama_barang', 'stok', 'stok_minimum', 'satuan'])
            ->map(fn ($p) => [
                'kode_barang' => $p->kode_barang,
                'nama_barang' => $p->nama_barang,
                'stok' => (int) $p->stok,
                'stok_minimum' => (int) $p->stok_minimum,
                'satuan' => $p->satuan,
            ])
            ->toArray();
    }

    /**
     * Statistik status anggota & karyawan.
     *
     * Kategori:
     * - anggota: aktif, aktif purna, diblokir, pasif purna
     * - karyawan: aktif, diblokir, pasif purna
     */
    protected function anggotaStats(?string $storeId): array
    {
        $query = Anggota::query();

        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        $rows = (clone $query)
            ->selectRaw("
                status,
                COUNT(*) FILTER (WHERE status_aktif = true AND status_purna = false AND status_limit = false) AS aktif,
                COUNT(*) FILTER (WHERE status_purna = true AND status_aktif = true) AS aktif_purna,
                COUNT(*) FILTER (WHERE status_limit = true) AS diblokir,
                COUNT(*) FILTER (WHERE status_purna = true AND status_aktif = false) AS pasif_purna
            ")
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $get = fn (string $status, string $col): int => (int) ($rows[$status]->{$col} ?? 0);

        $anggota = [
            'aktif' => $get('anggota', 'aktif'),
            'aktif_purna' => $get('anggota', 'aktif_purna'),
            'diblokir' => $get('anggota', 'diblokir'),
            'pasif_purna' => $get('anggota', 'pasif_purna'),
        ];

        $karyawan = [
            'aktif' => $get('karyawan', 'aktif'),
            'diblokir' => $get('karyawan', 'diblokir'),
            'pasif_purna' => $get('karyawan', 'pasif_purna'),
        ];

        return [
            'anggota' => $anggota,
            'karyawan' => $karyawan,
            'total' => (int) (clone $query)->count(),
        ];
    }
}
