<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Stok;
use App\Models\StokRiwayat;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StokController extends Controller
{
    /**
     * Stok terkini — dari tabel stok per-toko.
     *
     * Saat toko tertentu dipilih: tampilkan stok toko itu.
     * Saat "Semua Toko" (store_id = all / kosong): tampilkan breakdown
     * kolom per-toko (Pusat, K1..K5) + total.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');
        $showAll = ! $storeId || $storeId === 'all';

        $limit = (int) $request->input('limit', 25);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 25;

        $stores = Store::query()->orderBy('id')->get(['id', 'kode', 'nama']);

        $query = Produk::query()
            ->with(['store', 'supplier', 'stoks'])
            ->orderBy('nama_barang');

        // Scope ke toko aktif (kecuali "all")
        if (! $showAll) {
            $query->where('store_id', $storeId);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'ilike', "%{$search}%")
                    ->orWhere('kode_barang', 'ilike', "%{$search}%")
                    ->orWhere('kategori', 'ilike', "%{$search}%");
            });
        }

        $supplierId = $request->input('supplier_id', '');
        if ($supplierId !== '' && $supplierId !== null) {
            $query->where('supplier_id', $supplierId);
        }

        $kategori = trim((string) $request->input('kategori', ''));
        if ($kategori !== '') {
            $query->where('kategori', $kategori);
        }

        $produk = $query->paginate($limit)->withQueryString();

        // Mapping: siapkan field stok per toko + total + status menipis
        $produk->getCollection()->transform(function ($p) use ($stores, $showAll) {
            $stokRows = $p->stoks->keyBy('store_id');

            $perToko = [];
            foreach ($stores as $s) {
                $row = $stokRows->get($s->id);
                $perToko[$s->id] = [
                    'kode' => $s->kode,
                    'nama' => $s->nama,
                    'stok' => (int) ($row->stok ?? 0),
                    'stok_minimum' => (int) ($row->stok_minimum ?? 0),
                ];
            }

            $totalStok = (int) $stokRows->sum('stok');
            $lowStore = $showAll
                ? $stokRows->contains(fn ($r) => $r->stok <= $r->stok_minimum)
                : ($p->stokDi($showAll ? null : (int) session('store_id')) <= $p->stokMinimumDi($showAll ? null : (int) session('store_id')));

            $p->setAttribute('stok_per_toko', $perToko);
            $p->setAttribute('stok_total', $totalStok);
            $p->setAttribute('low_stock', (bool) $lowStore);

            return $p;
        });

        // Statistik ringkas (berdasar scope toko aktif)
        $statQuery = Stok::query()->whereIn('produk_id', $produk->getCollection()->pluck('id'));
        if (! $showAll) {
            $statQuery->where('store_id', $storeId);
        }

        $lowStockCount = (clone $statQuery)
            ->whereRaw('stok <= stok_minimum')
            ->distinct('produk_id')
            ->count('produk_id');

        $totalStok = (clone $statQuery)->sum('stok');
        $nilaiStok = Stok::query()
            ->whereIn('produk_id', $produk->getCollection()->pluck('id'))
            ->when(! $showAll, fn ($q) => $q->where('stok.store_id', $storeId))
            ->join('produk', 'produk.id', '=', 'stok.produk_id')
            ->sum(DB::raw('stok.stok * produk.harga_beli'));

        return Inertia::render('Stok/Index', [
            'produk' => $produk,
            'stores' => $stores,
            'show_all' => $showAll,
            'stats' => [
                'total_stok' => $totalStok,
                'nilai_stok' => $nilaiStok,
                'low_stock' => $lowStockCount,
            ],
            'suppliers' => Supplier::orderBy('nama')->get(['id', 'nama']),
            'kategori_list' => Produk::query()
                ->when(! $showAll, fn ($q) => $q->where('store_id', $storeId))
                ->whereNotNull('kategori')
                ->where('kategori', '!=', '')
                ->distinct()
                ->orderBy('kategori')
                ->pluck('kategori'),
            'filters' => [
                'search' => $request->input('search', ''),
                'supplier_id' => $request->input('supplier_id', ''),
                'kategori' => $kategori,
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Stok bulanan — mutasi per bulan dari tabel stok_riwayat.
     */
    public function bulanan(Request $request): Response
    {
        $storeId = session('store_id');
        $showAll = ! $storeId || $storeId === 'all';

        $bulan = $request->input('bulan', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $bulan = now()->format('Y-m');
        }

        [$tahun, $bulanAngka] = explode('-', $bulan);
        $awalBulan = "{$tahun}-{$bulanAngka}-01";
        $akhirBulan = date('Y-m-t', strtotime($awalBulan));

        $limit = (int) $request->input('limit', 25);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 25;

        $search = trim((string) $request->input('search', ''));

        $query = Produk::query()
            ->with(['store', 'supplier', 'stoks'])
            ->whereHas('stokRiwayat', function ($q) use ($awalBulan, $akhirBulan, $storeId, $showAll) {
                $q->whereBetween('tanggal', [$awalBulan, $akhirBulan]);
                if (! $showAll) {
                    $q->where('store_id', $storeId);
                }
            })
            ->orderBy('nama_barang');

        if (! $showAll) {
            $query->where('store_id', $storeId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'ilike', "%{$search}%")
                    ->orWhere('kode_barang', 'ilike', "%{$search}%");
            });
        }

        $supplierId = $request->input('supplier_id', '');
        if ($supplierId !== '' && $supplierId !== null) {
            $query->where('supplier_id', $supplierId);
        }

        $kategori = trim((string) $request->input('kategori', ''));
        if ($kategori !== '') {
            $query->where('kategori', $kategori);
        }

        $produk = $query->paginate($limit)->withQueryString();

        $activeStoreId = $showAll ? null : (int) $storeId;

        $rows = $produk->getCollection()->map(function ($p) use ($awalBulan, $akhirBulan, $activeStoreId) {
            $masuk = (int) StokRiwayat::where('produk_id', $p->id)
                ->where('tipe', 'masuk')
                ->when($activeStoreId, fn ($q) => $q->where('store_id', $activeStoreId))
                ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
                ->sum('qty');
            $keluar = (int) StokRiwayat::where('produk_id', $p->id)
                ->where('tipe', 'keluar')
                ->when($activeStoreId, fn ($q) => $q->where('store_id', $activeStoreId))
                ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
                ->sum('qty');
            $penyesuaian = (int) StokRiwayat::where('produk_id', $p->id)
                ->where('tipe', 'penyesuaian')
                ->when($activeStoreId, fn ($q) => $q->where('store_id', $activeStoreId))
                ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
                ->sum('qty');

            $stokAkhir = $p->stokDi($activeStoreId);

            return [
                'id' => $p->id,
                'kode_barang' => $p->kode_barang,
                'nama_barang' => $p->nama_barang,
                'kategori' => $p->kategori,
                'satuan' => $p->satuan,
                'stok_awal' => $stokAkhir - $masuk + $keluar,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'penyesuaian' => $penyesuaian,
                'stok_akhir' => $stokAkhir,
                'supplier' => $p->supplier?->nama,
            ];
        });

        $produk->setCollection($rows);

        return Inertia::render('Stok/Index', [
            'mode' => 'bulanan',
            'bulan' => $bulan,
            'produk' => $produk,
            'suppliers' => Supplier::orderBy('nama')->get(['id', 'nama']),
            'kategori_list' => Produk::query()
                ->when(! $showAll, fn ($q) => $q->where('store_id', $storeId))
                ->whereNotNull('kategori')
                ->where('kategori', '!=', '')
                ->distinct()
                ->orderBy('kategori')
                ->pluck('kategori'),
            'filters' => [
                'search' => $request->input('search', ''),
                'supplier_id' => $request->input('supplier_id', ''),
                'kategori' => $kategori,
                'limit' => $limit,
            ],
        ]);
    }
}
