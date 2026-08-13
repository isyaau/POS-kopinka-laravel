<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePembelianRequest;
use App\Http\Requests\UpdatePembelianRequest;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Produk;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PembelianController extends Controller
{
    /**
     * Menampilkan daftar pembelian, di-scope sesuai toko aktif.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = Pembelian::query()
            ->with(['supplier', 'store', 'details'])
            ->orderByDesc('tanggal');

        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('no_pembelian', 'ilike', "%{$search}%")
                    ->orWhere('no_faktur', 'ilike', "%{$search}%")
                    ->orWhere('nama_supplier', 'ilike', "%{$search}%");
            });
        }

        $pembelian = $query->paginate($limit)->withQueryString();

        return Inertia::render('Pembelian/Index', [
            'pembelian' => $pembelian,
            'produk' => Produk::with('stoks')
                ->orderBy('nama_barang')
                ->get(['id', 'kode_barang', 'nama_barang', 'harga_beli', 'harga_jual'])
                ->map(function ($p) use ($storeId) {
                    $sid = ($storeId && $storeId !== 'all') ? (int) $storeId : null;
                    $p->stok = $p->stokDi($sid);

                    return $p;
                }),
            'filters' => [
                'search' => $request->input('search', ''),
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Hitung rantai nilai pembelian dari daftar item.
     *
     * @param  array  $items  Item mentah dari request
     * @return array{nilai: float, subtotal: float, total: float, details: array}
     */
    protected function hitungPembelian(array $items, float $diskon, float $ppnMasukan, bool $hargaJualTermasukPpn): array
    {
        $nilai = 0;
        $details = [];

        foreach ($items as $item) {
            $hargaBeli = (float) ($item['harga_beli'] ?? 0);
            $diskonItem = (float) ($item['diskon_item'] ?? 0);
            $qty = (int) ($item['qty'] ?? 1);

            $subtotalItem = max(0, $qty * ($hargaBeli - $diskonItem));
            $nilai += $subtotalItem;

            $produk = isset($item['produk_id']) ? Produk::find($item['produk_id']) : null;

            $details[] = [
                'produk_id' => $item['produk_id'] ?? null,
                'nama_barang' => $item['nama_barang'] ?? $produk?->nama_barang,
                'qty' => $qty,
                'harga_beli' => $hargaBeli,
                'diskon_item' => $diskonItem,
                'subtotal' => $subtotalItem,
                'harga_jual' => (float) ($item['harga_jual'] ?? $produk?->harga_jual ?? 0),
                'tanggal_expired' => $item['tanggal_expired'] ?? null,
            ];
        }

        $subtotal = max(0, $nilai - $diskon);

        // Bila harga jual sudah termasuk PPN, PPN masukan tidak ditambahkan lagi
        // ke total; sebaliknya total = subtotal + ppn_masukan.
        $total = $hargaJualTermasukPpn ? $subtotal : $subtotal + $ppnMasukan;

        return [
            'nilai' => $nilai,
            'subtotal' => $subtotal,
            'total' => $total,
            'details' => $details,
        ];
    }

    /**
     * Simpan pembelian baru + detail item + tambah stok + update master produk.
     */
    public function store(StorePembelianRequest $request): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        $store = Store::find(($storeId && $storeId !== 'all') ? $storeId : null);

        $noManual = trim((string) ($validated['no_pembelian'] ?? ''));
        $autoNo = $noManual === '';

        try {
            $pembelian = null;

            // Retry hingga 3x bila no_pembelian auto bentrok.
            for ($attempt = 1; $attempt <= 3 && $pembelian === null; $attempt++) {
                try {
                    $pembelian = DB::transaction(function () use ($validated, $store, $autoNo, $noManual) {
                        $items = $validated['items'] ?? [];

                        $diskon = (float) ($validated['diskon'] ?? 0);
                        $ppnMasukan = (float) ($validated['ppn_masukan'] ?? 0);
                        $hargaJualTermasukPpn = (bool) ($validated['harga_jual_termasuk_ppn'] ?? false);

                        $hitung = $this->hitungPembelian($items, $diskon, $ppnMasukan, $hargaJualTermasukPpn);

                        $total = $hitung['total'];
                        $jenisBayar = $validated['jenis_bayar'] ?? 'tunai';

                        $sisaHutang = $jenisBayar === 'kredit' ? $total : 0;

                        $supplier = isset($validated['supplier_id']) ? Supplier::find($validated['supplier_id']) : null;

                        $pembelian = Pembelian::create([
                            'no_pembelian' => $autoNo ? (new Pembelian())->generateNoPembelian($store) : $noManual,
                            'no_faktur' => $validated['no_faktur'] ?? null,
                            'supplier_id' => $validated['supplier_id'] ?? null,
                            'nama_supplier' => $validated['nama_supplier'] ?? $supplier?->nama,
                            'tanggal' => $validated['tanggal'] ?? now()->toDateString(),
                            'terlampir_bukti_ppn' => $validated['terlampir_bukti_ppn'] ?? false,
                            'harga_jual_termasuk_ppn' => $hargaJualTermasukPpn,
                            'nilai' => $hitung['nilai'],
                            'diskon' => $diskon,
                            'subtotal' => $hitung['subtotal'],
                            'ppn_masukan' => $ppnMasukan,
                            'total' => $total,
                            'jenis_bayar' => $jenisBayar,
                            'sisa_hutang' => $sisaHutang,
                            'status' => $validated['status'] ?? 'selesai',
                            'keterangan' => $validated['keterangan'] ?? null,
                            'store_id' => $store?->id,
                        ]);

                        foreach ($hitung['details'] as $detail) {
                            PembelianDetail::create(array_merge($detail, ['pembelian_id' => $pembelian->id]));

                            // Tambah stok produk
                            if ($detail['produk_id']) {
                                $produk = Produk::find($detail['produk_id']);
                                if ($produk) {
                                    $produk->tambahStok($detail['qty'], "Pembelian {$pembelian->no_pembelian}", $store?->id);

                                    // Update master produk: harga beli, harga jual, tgl expired
                                    $update = [];
                                    if ($detail['harga_beli'] > 0) {
                                        $update['harga_beli'] = $detail['harga_beli'];
                                    }
                                    if ($detail['harga_jual'] > 0) {
                                        $update['harga_jual'] = $detail['harga_jual'];
                                    }
                                    if (! empty($detail['tanggal_expired'])) {
                                        $update['tanggal_expired'] = $detail['tanggal_expired'];
                                    }
                                    if (! empty($update)) {
                                        $produk->update($update);
                                    }
                                }
                            } else {
                                // Produk baru (belum ada di database) → buat otomatis
                                $nama = trim((string) ($detail['nama_barang'] ?? ''));
                                if ($nama !== '') {
                                    $produk = Produk::create([
                                        'kode_barang' => (new Produk())->generateKode(),
                                        'nama_barang' => $nama,
                                        'harga_beli' => $detail['harga_beli'],
                                        'harga_jual' => $detail['harga_jual'],
                                        'tanggal_expired' => $detail['tanggal_expired'] ?: null,
                                        'store_id' => $store?->id,
                                        'supplier_id' => $validated['supplier_id'] ?? null,
                                    ]);
                                    $produk->tambahStok($detail['qty'], "Pembelian {$pembelian->no_pembelian}", $store?->id);
                                }
                            }
                        }

                        return $pembelian;
                    });
                } catch (UniqueConstraintViolationException $e) {
                    if (! $autoNo) {
                        throw $e;
                    }

                    $pembelian = null;
                }
            }

            if ($pembelian === null) {
                throw new \RuntimeException('Gagal membuat nomor pembelian unik. Silakan coba lagi.');
            }

            return back()->with('success', "Pembelian {$pembelian->no_pembelian} berhasil disimpan.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyimpan pembelian: ' . $e->getMessage());
        }
    }

    /**
     * Perbarui pembelian: kembalikan stok lama, hapus detail lama, simpan baru.
     */
    public function update(UpdatePembelianRequest $request, Pembelian $pembelian): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        $storeIdFinal = ($storeId && $storeId !== 'all') ? $storeId : null;

        try {
            DB::transaction(function () use ($pembelian, $validated, $storeIdFinal) {
                // Kembalikan stok dari detail lama
                foreach ($pembelian->details as $detail) {
                    if ($detail->produk_id) {
                        $produk = Produk::find($detail->produk_id);
                        if ($produk) {
                            $produk->kurangiStok($detail->qty, "Reversi pembelian {$pembelian->no_pembelian}", $storeIdFinal);
                        }
                    }
                }

                // Hapus detail lama
                $pembelian->details()->delete();

                $items = $validated['items'] ?? [];

                $diskon = (float) ($validated['diskon'] ?? 0);
                $ppnMasukan = (float) ($validated['ppn_masukan'] ?? 0);
                $hargaJualTermasukPpn = (bool) ($validated['harga_jual_termasuk_ppn'] ?? false);

                $hitung = $this->hitungPembelian($items, $diskon, $ppnMasukan, $hargaJualTermasukPpn);

                $total = $hitung['total'];
                $jenisBayar = $validated['jenis_bayar'] ?? 'tunai';

                $sisaHutang = $jenisBayar === 'kredit' ? $total : 0;

                $supplier = isset($validated['supplier_id']) ? Supplier::find($validated['supplier_id']) : null;

                $pembelian->update([
                    'no_faktur' => $validated['no_faktur'] ?? null,
                    'supplier_id' => $validated['supplier_id'] ?? null,
                    'nama_supplier' => $validated['nama_supplier'] ?? $supplier?->nama,
                    'tanggal' => $validated['tanggal'] ?? now()->toDateString(),
                    'terlampir_bukti_ppn' => $validated['terlampir_bukti_ppn'] ?? false,
                    'harga_jual_termasuk_ppn' => $hargaJualTermasukPpn,
                    'nilai' => $hitung['nilai'],
                    'diskon' => $diskon,
                    'subtotal' => $hitung['subtotal'],
                    'ppn_masukan' => $ppnMasukan,
                    'total' => $total,
                    'jenis_bayar' => $jenisBayar,
                    'sisa_hutang' => $sisaHutang,
                    'status' => $validated['status'] ?? 'selesai',
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);

                foreach ($hitung['details'] as $detail) {
                    PembelianDetail::create(array_merge($detail, ['pembelian_id' => $pembelian->id]));

                    if ($detail['produk_id']) {
                        $produk = Produk::find($detail['produk_id']);
                        if ($produk) {
                            $produk->tambahStok($detail['qty'], "Pembelian {$pembelian->no_pembelian}", $storeIdFinal);

                            $update = [];
                            if ($detail['harga_beli'] > 0) {
                                $update['harga_beli'] = $detail['harga_beli'];
                            }
                            if ($detail['harga_jual'] > 0) {
                                $update['harga_jual'] = $detail['harga_jual'];
                            }
                            if (! empty($detail['tanggal_expired'])) {
                                $update['tanggal_expired'] = $detail['tanggal_expired'];
                            }
                            if (! empty($update)) {
                                $produk->update($update);
                            }
                        }
                    } else {
                        // Produk baru → buat otomatis (jika belum ada dengan nama sama)
                        $nama = trim((string) ($detail['nama_barang'] ?? ''));
                        if ($nama !== '') {
                            $produk = Produk::where('nama_barang', $nama)->first();
                            if (! $produk) {
                                $produk = Produk::create([
                                    'kode_barang' => (new Produk())->generateKode(),
                                    'nama_barang' => $nama,
                                    'harga_beli' => $detail['harga_beli'],
                                    'harga_jual' => $detail['harga_jual'],
                                    'tanggal_expired' => $detail['tanggal_expired'] ?: null,
                                    'store_id' => $storeIdFinal,
                                    'supplier_id' => $validated['supplier_id'] ?? null,
                                ]);
                            }
                            $produk->tambahStok($detail['qty'], "Pembelian {$pembelian->no_pembelian}", $storeIdFinal);
                        }
                    }
                }
            });

            return back()->with('success', "Pembelian {$pembelian->no_pembelian} berhasil diperbarui.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memperbarui pembelian: ' . $e->getMessage());
        }
    }

    /**
     * Hapus pembelian + kembalikan stok.
     */
    public function destroy(Pembelian $pembelian): RedirectResponse
    {
        $storeId = session('store_id');
        $storeIdFinal = ($storeId && $storeId !== 'all') ? $storeId : null;

        try {
            DB::transaction(function () use ($pembelian, $storeIdFinal) {
                foreach ($pembelian->details as $detail) {
                    if ($detail->produk_id) {
                        $produk = Produk::find($detail->produk_id);
                        if ($produk) {
                            $produk->kurangiStok($detail->qty, "Hapus pembelian {$pembelian->no_pembelian}", $storeIdFinal);
                        }
                    }
                }

                $pembelian->delete();
            });

            return back()->with('success', "Pembelian {$pembelian->no_pembelian} berhasil dihapus.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus pembelian: ' . $e->getMessage());
        }
    }
}
