<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\StokRiwayat;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MutasiController extends Controller
{
    /**
     * Riwayat mutasi stok (ledger) dari tabel stok_riwayat.
     *
     * Tidak ada tabel baru; saldo berjalan (running balance) dihitung
     * on-the-fly via window function PostgreSQL.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');
        $showAll = ! $storeId || $storeId === 'all';

        $limit = (int) $request->input('limit', 25);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 25;

        $query = StokRiwayat::query()
            ->with(['produk', 'store'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        if (! $showAll) {
            $query->where('store_id', $storeId);
        }

        if ($sid = $request->input('store_id_filter', '')) {
            if ($sid !== '' && $sid !== null && $sid !== 'all') {
                $query->where('store_id', $sid);
            }
        }

        if ($pid = $request->input('produk_id', '')) {
            $query->where('produk_id', $pid);
        }

        if ($tipe = trim((string) $request->input('tipe', ''))) {
            $query->where('tipe', $tipe);
        }

        if ($dari = trim((string) $request->input('dari', ''))) {
            $query->where('tanggal', '>=', $dari);
        }
        if ($sampai = trim((string) $request->input('sampai', ''))) {
            $query->where('tanggal', '<=', $sampai);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->whereHas('produk', function ($q) use ($search) {
                $q->where('nama_barang', 'ilike', "%{$search}%")
                    ->orWhere('kode_barang', 'ilike', "%{$search}%");
            });
        }

        $rows = $query->paginate($limit)->withQueryString();

        // Hitung saldo berjalan per produk (window function).
        $this->attachSaldo($rows->getCollection());

        return Inertia::render('Mutasi/Index', [
            'mutasi' => $rows,
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
            'produk' => Produk::orderBy('nama_barang')->get(['id', 'kode_barang', 'nama_barang']),
            'show_all' => $showAll,
            'filters' => [
                'search' => $request->input('search', ''),
                'store_id_filter' => $request->input('store_id_filter', $showAll ? 'all' : $storeId),
                'produk_id' => $request->input('produk_id', ''),
                'tipe' => $request->input('tipe', ''),
                'dari' => $request->input('dari', ''),
                'sampai' => $request->input('sampai', ''),
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Hitung saldo berjalan per produk (masuk +, keluar/penyesuaian -).
     */
    protected function attachSaldo($collection): void
    {
        $produkIds = $collection->pluck('produk_id')->unique()->filter()->values();

        if ($produkIds->isEmpty()) {
            return;
        }

        // Ambil semua riwayat produk terkait (urut asc) untuk hitung saldo akurat.
        $all = StokRiwayat::query()
            ->whereIn('produk_id', $produkIds)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get(['id', 'produk_id', 'store_id', 'tipe', 'qty']);

        // Saldo per (produk_id, store_id) kumulatif sampai tiap baris.
        $saldo = [];
        $lookup = [];
        foreach ($all as $r) {
            $key = $r->produk_id . ':' . ($r->store_id ?? 0);
            $delta = $r->tipe === 'masuk' ? (int) $r->qty : -(int) $r->qty;
            $saldo[$key] = ($saldo[$key] ?? 0) + $delta;
            $lookup[$r->id] = $saldo[$key];
        }

        $collection->transform(function ($row) use ($lookup) {
            $key = $row->produk_id . ':' . ($row->store_id ?? 0);
            $row->setAttribute('saldo', $lookup[$row->id] ?? ($saldo[$key] ?? 0));

            return $row;
        });
    }
}
