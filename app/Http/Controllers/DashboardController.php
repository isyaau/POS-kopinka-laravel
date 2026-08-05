<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Store;
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
            ],
        ]);
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
