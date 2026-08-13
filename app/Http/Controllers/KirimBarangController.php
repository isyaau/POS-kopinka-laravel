<?php

namespace App\Http\Controllers;

use App\Models\KirimBarang;
use App\Models\KirimBarangDetail;
use App\Models\Produk;
use App\Models\Stok;
use App\Models\Store;
use App\Models\StokRiwayat;
use App\Http\Requests\StoreKirimBarangRequest;
use App\Http\Requests\UpdateKirimBarangRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KirimBarangController extends Controller
{
    /**
     * List kirim barang dengan scope toko aktif + search + pagination.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');
        $showAll = ! $storeId || $storeId === 'all';

        $limit = (int) $request->input('limit', 25);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 25;

        $query = KirimBarang::query()
            ->with(['storeAsal', 'storeTujuan', 'store', 'details'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        if (! $showAll) {
            $query->where(function ($q) use ($storeId) {
                $q->where('store_asal_id', $storeId)
                    ->orWhere('store_tujuan_id', $storeId);
            });
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('no_kirim', 'ilike', "%{$search}%")
                    ->orWhere('nama_store_asal', 'ilike', "%{$search}%")
                    ->orWhere('nama_store_tujuan', 'ilike', "%{$search}%")
                    ->orWhere('keterangan', 'ilike', "%{$search}%");
            });
        }

        if ($status = trim((string) $request->input('status', ''))) {
            $query->where('status', $status);
        }

        $kirim = $query->paginate($limit)->withQueryString();

        return Inertia::render('KirimBarang/Index', [
            'kirim' => $kirim,
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
            'produk' => Produk::orderBy('nama_barang')
                ->with('stoks')
                ->get(['id', 'kode_barang', 'nama_barang', 'satuan', 'harga_beli']),
            'show_all' => $showAll,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Simpan kirim barang baru + mutasi stok antar toko.
     */
    public function store(StoreKirimBarangRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $storeId = session('store_id');

        DB::transaction(function () use ($validated, $storeId) {
            $asal = Store::findOrFail($validated['store_asal_id']);
            $tujuan = Store::findOrFail($validated['store_tujuan_id']);

            $details = $validated['details'];
            $totalItem = 0;
            $totalNilai = 0;

            foreach ($details as $d) {
                $totalItem += (int) $d['qty'];
                $totalNilai += (float) ($d['qty'] * ($d['harga_beli'] ?? 0));
            }

            $kirim = KirimBarang::create([
                'no_kirim' => $this->generateNoKirim($asal),
                'tanggal' => $validated['tanggal'],
                'store_asal_id' => $asal->id,
                'nama_store_asal' => $asal->nama,
                'store_tujuan_id' => $tujuan->id,
                'nama_store_tujuan' => $tujuan->nama,
                'status' => $validated['status'] ?? 'draft',
                'total_item' => $totalItem,
                'total_nilai' => $totalNilai,
                'keterangan' => $validated['keterangan'] ?? null,
                'store_id' => $asal->id,
            ]);

            $qtyById = [];
            foreach ($details as $d) {
                $produk = Produk::findOrFail($d['produk_id']);
                $qty = (int) $d['qty'];
                $hargaBeli = (float) ($d['harga_beli'] ?? $produk->harga_beli);

                KirimBarangDetail::create([
                    'kirim_barang_id' => $kirim->id,
                    'produk_id' => $produk->id,
                    'nama_barang' => $produk->nama_barang,
                    'qty' => $qty,
                    'harga_beli' => $hargaBeli,
                    'subtotal' => $qty * $hargaBeli,
                ]);
                $qtyById[$produk->id] = $qty;
            }

            // Mutasi stok hanya bila status dikirim/selesai
            if (in_array($kirim->status, ['dikirim', 'selesai'])) {
                $this->mutasiStok($kirim, $qtyById, $validated['tanggal']);
            }
        });

        return back()->with('success', 'Kirim barang berhasil disimpan.');
    }

    /**
     * Update kirim barang + reversi/re-mutasi stok.
     */
    public function update(UpdateKirimBarangRequest $request, KirimBarang $kirimBarang): RedirectResponse
    {
        $validated = $request->validated();
        $wasMutated = in_array($kirimBarang->status, ['dikirim', 'selesai']);

        DB::transaction(function () use ($validated, $kirimBarang, $wasMutated) {
            $oldQtyById = [];
            if ($wasMutated) {
                foreach ($kirimBarang->details as $d) {
                    $oldQtyById[$d->produk_id] = (int) $d->qty;
                }
                // Reversi stok lama
                $this->reversiStok($kirimBarang, $oldQtyById);
            }

            $asal = Store::findOrFail($validated['store_asal_id']);
            $tujuan = Store::findOrFail($validated['store_tujuan_id']);

            $details = $validated['details'];
            $totalItem = 0;
            $totalNilai = 0;
            foreach ($details as $d) {
                $totalItem += (int) $d['qty'];
                $totalNilai += (float) ($d['qty'] * ($d['harga_beli'] ?? 0));
            }

            $kirimBarang->update([
                'tanggal' => $validated['tanggal'],
                'store_asal_id' => $asal->id,
                'nama_store_asal' => $asal->nama,
                'store_tujuan_id' => $tujuan->id,
                'nama_store_tujuan' => $tujuan->nama,
                'status' => $validated['status'] ?? 'draft',
                'total_item' => $totalItem,
                'total_nilai' => $totalNilai,
                'keterangan' => $validated['keterangan'] ?? null,
            ]);

            // Hapus detail lama, buat baru
            $kirimBarang->details()->delete();

            $newQtyById = [];
            foreach ($details as $d) {
                $produk = Produk::findOrFail($d['produk_id']);
                $qty = (int) $d['qty'];
                $hargaBeli = (float) ($d['harga_beli'] ?? $produk->harga_beli);

                KirimBarangDetail::create([
                    'kirim_barang_id' => $kirimBarang->id,
                    'produk_id' => $produk->id,
                    'nama_barang' => $produk->nama_barang,
                    'qty' => $qty,
                    'harga_beli' => $hargaBeli,
                    'subtotal' => $qty * $hargaBeli,
                ]);
                $newQtyById[$produk->id] = $qty;
            }

            if (in_array($kirimBarang->status, ['dikirim', 'selesai'])) {
                $this->mutasiStok($kirimBarang, $newQtyById, $validated['tanggal']);
            }
        });

        return back()->with('success', 'Kirim barang berhasil diperbarui.');
    }

    /**
     * Hapus kirim barang + reversi stok bila sudah dimutasi.
     */
    public function destroy(KirimBarang $kirimBarang): RedirectResponse
    {
        DB::transaction(function () use ($kirimBarang) {
            if (in_array($kirimBarang->status, ['dikirim', 'selesai'])) {
                $qtyById = [];
                foreach ($kirimBarang->details as $d) {
                    $qtyById[$d->produk_id] = (int) $d->qty;
                }
                $this->reversiStok($kirimBarang, $qtyById);
            }

            $kirimBarang->details()->delete();
            $kirimBarang->delete();
        });

        return back()->with('success', 'Kirim barang berhasil dihapus.');
    }

    /**
     * Kurangi stok asal + tambah stok tujuan + riwayat per produk.
     */
    protected function mutasiStok(KirimBarang $kirim, array $qtyById, string $tanggal): void
    {
        $asalId = $kirim->store_asal_id;
        $tujuanId = $kirim->store_tujuan_id;

        foreach ($qtyById as $produkId => $qty) {
            $produk = Produk::find($produkId);
            if (! $produk) {
                continue;
            }

            $rowAsal = Stok::firstOrCreate(
                ['produk_id' => $produkId, 'store_id' => $asalId],
                ['stok' => 0, 'stok_minimum' => 0],
            );
            $qtyKeluar = min($qty, $rowAsal->stok);
            if ($qtyKeluar > 0) {
                $rowAsal->decrement('stok', $qtyKeluar);
            }

            StokRiwayat::create([
                'produk_id' => $produkId,
                'tipe' => 'keluar',
                'qty' => $qtyKeluar,
                'tanggal' => $tanggal,
                'keterangan' => "Kirim barang {$kirim->no_kirim} → {$kirim->nama_store_tujuan}",
                'store_id' => $asalId,
            ]);

            $rowTujuan = Stok::firstOrCreate(
                ['produk_id' => $produkId, 'store_id' => $tujuanId],
                ['stok' => 0, 'stok_minimum' => 0],
            );
            $rowTujuan->increment('stok', $qty);

            StokRiwayat::create([
                'produk_id' => $produkId,
                'tipe' => 'masuk',
                'qty' => $qty,
                'tanggal' => $tanggal,
                'keterangan' => "Terima barang {$kirim->no_kirim} dari {$kirim->nama_store_asal}",
                'store_id' => $tujuanId,
            ]);
        }
    }

    /**
     * Balikkan mutasi: kembalikan stok asal, kurangi stok tujuan.
     */
    protected function reversiStok(KirimBarang $kirim, array $qtyById): void
    {
        $asalId = $kirim->store_asal_id;
        $tujuanId = $kirim->store_tujuan_id;

        foreach ($qtyById as $produkId => $qty) {
            $produk = Produk::find($produkId);
            if (! $produk) {
                continue;
            }

            // Kembalikan ke asal
            $rowAsal = Stok::firstOrCreate(
                ['produk_id' => $produkId, 'store_id' => $asalId],
                ['stok' => 0, 'stok_minimum' => 0],
            );
            $rowAsal->increment('stok', $qty);

            StokRiwayat::create([
                'produk_id' => $produkId,
                'tipe' => 'masuk',
                'qty' => $qty,
                'tanggal' => $kirim->tanggal->toDateString(),
                'keterangan' => "Reversi kirim barang {$kirim->no_kirim}",
                'store_id' => $asalId,
            ]);

            // Kurangi dari tujuan (tidak boleh negatif)
            $rowTujuan = Stok::firstOrCreate(
                ['produk_id' => $produkId, 'store_id' => $tujuanId],
                ['stok' => 0, 'stok_minimum' => 0],
            );
            $qtyMasuk = min($qty, $rowTujuan->stok);
            if ($qtyMasuk > 0) {
                $rowTujuan->decrement('stok', $qtyMasuk);
            }

            StokRiwayat::create([
                'produk_id' => $produkId,
                'tipe' => 'keluar',
                'qty' => $qtyMasuk,
                'tanggal' => $kirim->tanggal->toDateString(),
                'keterangan' => "Reversi terima barang {$kirim->no_kirim}",
                'store_id' => $tujuanId,
            ]);
        }
    }

    /**
     * Generate no kirim unik per toko asal per hari.
     */
    protected function generateNoKirim(Store $asal): string
    {
        $kode = $asal->kode ? strtoupper(preg_replace('/[^A-Z0-9]/', '', $asal->kode)) : 'ALL';
        $prefix = 'KB' . $kode . now()->format('Ymd');

        $last = KirimBarang::where('no_kirim', 'like', $prefix . '%')
            ->orderByDesc('no_kirim')
            ->value('no_kirim');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }
}
