<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReturPembelianRequest;
use App\Http\Requests\UpdateReturPembelianRequest;
use App\Models\Pembelian;
use App\Models\Produk;
use App\Models\ReturPembelian;
use App\Models\ReturPembelianDetail;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReturPembelianController extends Controller
{
    /**
     * Menampilkan daftar retur/tukar pembelian, di-scope sesuai toko aktif.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = ReturPembelian::query()
            ->with(['supplier', 'store', 'details', 'pembelian'])
            ->orderByDesc('tanggal');

        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('no_retur', 'ilike', "%{$search}%")
                    ->orWhere('no_pembelian_asal', 'ilike', "%{$search}%")
                    ->orWhere('nama_supplier', 'ilike', "%{$search}%");
            });
        }

        $retur = $query->paginate($limit)->withQueryString();

        return Inertia::render('ReturPembelian/Index', [
            'retur' => $retur,
            'suppliers' => Supplier::orderBy('nama')->get(['id', 'kode', 'nama']),
            'produk' => Produk::orderBy('nama_barang')->get(['id', 'kode_barang', 'nama_barang', 'harga_beli', 'harga_jual', 'stok']),
            'pembelianList' => Pembelian::orderByDesc('tanggal')->get(['id', 'no_pembelian', 'supplier_id', 'nama_supplier', 'tanggal', 'total']),
            'filters' => [
                'search' => $request->input('search', ''),
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Hitung total retur dari daftar item.
     *
     * @return array{total_retur: float, details: array}
     */
    protected function hitungRetur(array $items): array
    {
        $totalRetur = 0;
        $details = [];

        foreach ($items as $item) {
            $hargaBeli = (float) ($item['harga_beli'] ?? 0);
            $qtyRetur = (int) ($item['qty_retur'] ?? 1);
            $subtotal = max(0, $qtyRetur * $hargaBeli);
            $totalRetur += $subtotal;

            $produk = isset($item['produk_id']) ? Produk::find($item['produk_id']) : null;
            $produkTukar = isset($item['produk_tukar_id']) ? Produk::find($item['produk_tukar_id']) : null;

            $details[] = [
                'produk_id' => $item['produk_id'] ?? null,
                'nama_barang' => $item['nama_barang'] ?? $produk?->nama_barang,
                'qty_retur' => $qtyRetur,
                'harga_beli' => $hargaBeli,
                'subtotal' => $subtotal,
                'produk_tukar_id' => $item['produk_tukar_id'] ?? null,
                'nama_barang_tukar' => $item['nama_barang_tukar'] ?? $produkTukar?->nama_barang,
                'qty_tukar' => (int) ($item['qty_tukar'] ?? 0),
            ];
        }

        return [
            'total_retur' => $totalRetur,
            'details' => $details,
        ];
    }

    /**
     * Simpan retur/tukar baru + update stok.
     */
    public function store(StoreReturPembelianRequest $request): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        $store = Store::find(($storeId && $storeId !== 'all') ? $storeId : null);

        try {
            $retur = DB::transaction(function () use ($validated, $store) {
                $items = $validated['items'] ?? [];
                $tipe = $validated['tipe'] ?? 'retur';

                $hitung = $this->hitungRetur($items);

                $supplier = isset($validated['supplier_id']) ? Supplier::find($validated['supplier_id']) : null;
                $pembelian = isset($validated['pembelian_id']) ? Pembelian::find($validated['pembelian_id']) : null;

                $retur = ReturPembelian::create([
                    'no_retur' => (new ReturPembelian())->generateNoRetur($store),
                    'pembelian_id' => $validated['pembelian_id'] ?? null,
                    'no_pembelian_asal' => $validated['no_pembelian_asal'] ?? $pembelian?->no_pembelian,
                    'supplier_id' => $validated['supplier_id'] ?? null,
                    'nama_supplier' => $validated['nama_supplier'] ?? $supplier?->nama,
                    'tanggal' => $validated['tanggal'] ?? now()->toDateString(),
                    'tipe' => $tipe,
                    'alasan' => $validated['alasan'] ?? null,
                    'total_retur' => $hitung['total_retur'],
                    'status' => $validated['status'] ?? 'selesai',
                    'keterangan' => $validated['keterangan'] ?? null,
                    'store_id' => $store?->id,
                ]);

                foreach ($hitung['details'] as $detail) {
                    ReturPembelianDetail::create(array_merge($detail, ['retur_pembelian_id' => $retur->id]));

                    // Retur: kurangi stok produk
                    if ($detail['produk_id']) {
                        $produk = Produk::find($detail['produk_id']);
                        if ($produk) {
                            $produk->kurangiStok($detail['qty_retur'], "Retur pembelian {$retur->no_retur}", $store?->id);
                        }
                    }

                    // Tukar: tambah stok produk pengganti
                    if ($tipe === 'tukar' && $detail['produk_tukar_id'] && $detail['qty_tukar'] > 0) {
                        $produkTukar = Produk::find($detail['produk_tukar_id']);
                        if ($produkTukar) {
                            $produkTukar->tambahStok($detail['qty_tukar'], "Tukar dari retur {$retur->no_retur}", $store?->id);
                        }
                    }
                }

                return $retur;
            });

            return back()->with('success', "Retur {$retur->no_retur} berhasil disimpan.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyimpan retur: ' . $e->getMessage());
        }
    }

    /**
     * Perbarui retur: kembalikan stok lama, hapus detail lama, simpan baru.
     */
    public function update(UpdateReturPembelianRequest $request, ReturPembelian $retur_pembelian): RedirectResponse
    {
        $retur = $retur_pembelian;

        $storeId = session('store_id');
        $validated = $request->validated();

        $storeIdFinal = ($storeId && $storeId !== 'all') ? $storeId : null;

        try {
            DB::transaction(function () use ($retur, $validated, $storeIdFinal) {
                // Kembalikan efek stok lama (balik: tambah stok retur, kurangi stok tukar)
                foreach ($retur->details as $detail) {
                    if ($detail->produk_id) {
                        $produk = Produk::find($detail->produk_id);
                        if ($produk) {
                            $produk->tambahStok($detail->qty_retur, "Reversi retur {$retur->no_retur}", $storeIdFinal);
                        }
                    }
                    if ($detail->produk_tukar_id && $detail->qty_tukar > 0) {
                        $produkTukar = Produk::find($detail->produk_tukar_id);
                        if ($produkTukar) {
                            $produkTukar->kurangiStok($detail->qty_tukar, "Reversi tukar {$retur->no_retur}", $storeIdFinal);
                        }
                    }
                }

                $retur->details()->delete();

                $items = $validated['items'] ?? [];
                $tipe = $validated['tipe'] ?? 'retur';

                $hitung = $this->hitungRetur($items);

                $supplier = isset($validated['supplier_id']) ? Supplier::find($validated['supplier_id']) : null;
                $pembelian = isset($validated['pembelian_id']) ? Pembelian::find($validated['pembelian_id']) : null;

                $retur->update([
                    'pembelian_id' => $validated['pembelian_id'] ?? null,
                    'no_pembelian_asal' => $validated['no_pembelian_asal'] ?? $pembelian?->no_pembelian,
                    'supplier_id' => $validated['supplier_id'] ?? null,
                    'nama_supplier' => $validated['nama_supplier'] ?? $supplier?->nama,
                    'tanggal' => $validated['tanggal'] ?? now()->toDateString(),
                    'tipe' => $tipe,
                    'alasan' => $validated['alasan'] ?? null,
                    'total_retur' => $hitung['total_retur'],
                    'status' => $validated['status'] ?? 'selesai',
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);

                foreach ($hitung['details'] as $detail) {
                    ReturPembelianDetail::create(array_merge($detail, ['retur_pembelian_id' => $retur->id]));

                    if ($detail['produk_id']) {
                        $produk = Produk::find($detail['produk_id']);
                        if ($produk) {
                            $produk->kurangiStok($detail['qty_retur'], "Retur pembelian {$retur->no_retur}", $storeIdFinal);
                        }
                    }

                    if ($tipe === 'tukar' && $detail['produk_tukar_id'] && $detail['qty_tukar'] > 0) {
                        $produkTukar = Produk::find($detail['produk_tukar_id']);
                        if ($produkTukar) {
                            $produkTukar->tambahStok($detail['qty_tukar'], "Tukar dari retur {$retur->no_retur}", $storeIdFinal);
                        }
                    }
                }
            });

            return back()->with('success', "Retur {$retur->no_retur} berhasil diperbarui.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memperbarui retur: ' . $e->getMessage());
        }
    }

    /**
     * Hapus retur + kembalikan stok.
     */
    public function destroy(ReturPembelian $retur_pembelian): RedirectResponse
    {
        $retur = $retur_pembelian;

        $storeId = session('store_id');
        $storeIdFinal = ($storeId && $storeId !== 'all') ? $storeId : null;

        try {
            DB::transaction(function () use ($retur, $storeIdFinal) {
                foreach ($retur->details as $detail) {
                    if ($detail->produk_id) {
                        $produk = Produk::find($detail->produk_id);
                        if ($produk) {
                            $produk->tambahStok($detail->qty_retur, "Hapus retur {$retur->no_retur}", $storeIdFinal);
                        }
                    }
                    if ($detail->produk_tukar_id && $detail->qty_tukar > 0) {
                        $produkTukar = Produk::find($detail->produk_tukar_id);
                        if ($produkTukar) {
                            $produkTukar->kurangiStok($detail->qty_tukar, "Hapus tukar {$retur->no_retur}", $storeIdFinal);
                        }
                    }
                }

                $retur->delete();
            });

            return back()->with('success', "Retur {$retur->no_retur} berhasil dihapus.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus retur: ' . $e->getMessage());
        }
    }
}
