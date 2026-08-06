<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\StokRiwayat;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StokController extends Controller
{
    /**
     * Stok terkini — dari stok produk saat ini.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $limit = (int) $request->input('limit', 25);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 25;

        $query = Produk::query()
            ->with(['store', 'supplier'])
            ->orderBy('nama_barang');

        if ($storeId && $storeId !== 'all') {
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

        // Statistik ringkas
        $totalStok = (clone $query)->sum('stok');
        $nilaiStok = (clone $query)->sum(\DB::raw('stok * harga_beli'));
        $lowStockCount = (clone $query)->whereColumn('stok', '<=', 'stok_minimum')->count();

        return Inertia::render('Stok/Index', [
            'produk' => $produk,
            'stats' => [
                'total_stok' => $totalStok,
                'nilai_stok' => $nilaiStok,
                'low_stock' => $lowStockCount,
            ],
            'suppliers' => Supplier::orderBy('nama')->get(['id', 'nama']),
            'kategori_list' => Produk::query()
                ->when($storeId && $storeId !== 'all', fn ($q) => $q->where('store_id', $storeId))
                ->whereNotNull('kategori')
                ->where('kategori', '!=', '')
                ->distinct()
                ->orderBy('kategori')
                ->pluck('kategori'),
            'filters' => [
                'search' => $request->input('search', ''),
                'supplier_id' => $request->input('supplier_id', ''),
                'kategori' => $request->input('kategori', ''),
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

        $bulan = $request->input('bulan', now()->format('Y-m'));
        // Validasi format YYYY-MM
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $bulan = now()->format('Y-m');
        }

        [$tahun, $bulanAngka] = explode('-', $bulan);
        $awalBulan = "{$tahun}-{$bulanAngka}-01";
        $akhirBulan = date('Y-m-t', strtotime($awalBulan));

        $limit = (int) $request->input('limit', 25);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 25;

        $search = trim((string) $request->input('search', ''));

        // Produk dengan riwayat pada bulan tersebut
        $query = Produk::query()
            ->with(['store', 'supplier'])
            ->whereHas('stokRiwayat', function ($q) use ($awalBulan, $akhirBulan) {
                $q->whereBetween('tanggal', [$awalBulan, $akhirBulan]);
            })
            ->orderBy('nama_barang');

        if ($storeId && $storeId !== 'all') {
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

        // Hitung mutasi per produk
        $rows = $produk->getCollection()->map(function ($p) use ($awalBulan, $akhirBulan) {
            $masuk = (int) StokRiwayat::where('produk_id', $p->id)
                ->where('tipe', 'masuk')
                ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
                ->sum('qty');
            $keluar = (int) StokRiwayat::where('produk_id', $p->id)
                ->where('tipe', 'keluar')
                ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
                ->sum('qty');
            $penyesuaian = (int) StokRiwayat::where('produk_id', $p->id)
                ->where('tipe', 'penyesuaian')
                ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
                ->sum('qty');

            return [
                'id' => $p->id,
                'kode_barang' => $p->kode_barang,
                'nama_barang' => $p->nama_barang,
                'kategori' => $p->kategori,
                'satuan' => $p->satuan,
                'stok_awal' => $p->stok - $masuk + $keluar,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'penyesuaian' => $penyesuaian,
                'stok_akhir' => $p->stok,
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
                ->when($storeId && $storeId !== 'all', fn ($q) => $q->where('store_id', $storeId))
                ->whereNotNull('kategori')
                ->where('kategori', '!=', '')
                ->distinct()
                ->orderBy('kategori')
                ->pluck('kategori'),
            'filters' => [
                'search' => $request->input('search', ''),
                'supplier_id' => $request->input('supplier_id', ''),
                'kategori' => $request->input('kategori', ''),
                'limit' => $limit,
            ],
        ]);
    }
}
