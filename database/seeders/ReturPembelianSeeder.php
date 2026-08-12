<?php

namespace Database\Seeders;

use App\Models\Pembelian;
use App\Models\Produk;
use App\Models\ReturPembelian;
use App\Models\ReturPembelianDetail;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ReturPembelianSeeder extends Seeder
{
    /**
     * Seed 100 retur/tukar pembelian dari supplier (idempotent, aman dijalankan ulang).
     *
     * - Tersebar di 6 store (pusat + K1..K5) secara round-robin.
     * - Memakai pembelian, supplier & produk yang sudah ada (hasil PembelianSeeder).
     * - ±60% tipe retur, ±40% tipe tukar (dengan produk pengganti).
     * - qty retur 1-20, harga beli mengikuti produk/pembelian asal.
     * - Seperti PembelianSeeder, hanya membuat data historis (tidak mengubah stok).
     */
    public function run(): void
    {
        $stores = Store::query()->orderBy('id')->get();
        if ($stores->isEmpty()) {
            $this->command?->warn('Tidak ada store. Jalankan RolePermissionSeeder dulu.');

            return;
        }

        $pembelianList = Pembelian::query()
            ->with('details')
            ->orderBy('id')
            ->get();

        if ($pembelianList->isEmpty()) {
            $this->command?->warn('Tidak ada pembelian. Jalankan PembelianSeeder dulu.');

            return;
        }

        $produkList = Produk::query()->orderBy('id')->get(['id', 'nama_barang', 'harga_beli']);
        if ($produkList->isEmpty()) {
            $this->command?->warn('Tidak ada produk. Jalankan ProdukSupplierSeeder dulu.');

            return;
        }

        $alasanOptions = [
            'Barang rusak / cacat saat diterima',
            'Barang kadaluarsa / mendekati kedaluwarsa',
            'Salah kirim (jenis atau varian tidak sesuai)',
            'Kualitas tidak sesuai pesanan',
            'Jumlah tidak sesuai faktur',
            'Kemasan penyok / sobek',
            'Barang tidak laku / dikembalikan supplier',
        ];

        $statusOptions = ['selesai', 'selesai', 'selesai', 'draft', 'batal'];

        $baru = 0;

        for ($i = 1; $i <= 100; $i++) {
            // Pilih store secara round-robin (idempotent)
            $store = $stores[($i - 1) % $stores->count()];

            // Pilih pembelian asal secara round-robin dari daftar pembelian
            $pembelian = $pembelianList[($i - 1) % $pembelianList->count()];

            // Tanggal deterministik (tidak lebih dari tanggal pembelian asal jika memungkinkan)
            $tanggal = now()->subDays((int) (($i * 5) % 60))->toDateString();

            $noRetur = $this->generateNoRetur($store->kode, $tanggal, $i);

            // Skip bila sudah ada (idempotent)
            if (ReturPembelian::where('no_retur', $noRetur)->exists()) {
                continue;
            }

            // Tipe: ±60% retur, ±40% tukar
            $tipe = ($i % 5 === 0 || $i % 5 === 1) ? 'tukar' : 'retur';

            // Pilih 1-2 item dari detail pembelian asal (fallback: produk acak)
            $itemCount = rand(1, 2);
            $sumberItems = $pembelian->details->isNotEmpty()
                ? $pembelian->details
                : collect([$pembelian]);

            $pickedDetails = $sumberItems
                ->shuffle()
                ->take(min($itemCount, $sumberItems->count()));

            $details = [];
            $totalRetur = 0;

            foreach ($pickedDetails as $detail) {
                $produk = isset($detail->produk_id)
                    ? $produkList->firstWhere('id', $detail->produk_id)
                    : null;

                $namaBarang = $detail->nama_barang ?? $produk?->nama_barang ?? 'Produk ' . $i;
                $hargaBeli = (float) ($detail->harga_beli ?? $produk?->harga_beli ?? rand(1000, 200000));
                $qtyRetur = rand(1, 20);

                $subtotal = max(0, $qtyRetur * $hargaBeli);
                $totalRetur += $subtotal;

                // Produk pengganti hanya untuk tipe tukar (pilih produk lain)
                $produkTukar = null;
                $qtyTukar = 0;
                if ($tipe === 'tukar') {
                    $kandidatTukar = $produkList->filter(fn ($p) => ! $produk || $p->id !== $produk->id);
                    $produkTukar = $kandidatTukar->isNotEmpty() ? $kandidatTukar->random() : $produkList->first();
                    $qtyTukar = rand(1, $qtyRetur);
                }

                $details[] = [
                    'produk_id' => $detail->produk_id ?? $produk?->id ?? null,
                    'nama_barang' => $namaBarang,
                    'qty_retur' => $qtyRetur,
                    'harga_beli' => $hargaBeli,
                    'subtotal' => $subtotal,
                    'produk_tukar_id' => $produkTukar?->id,
                    'nama_barang_tukar' => $produkTukar?->nama_barang,
                    'qty_tukar' => $qtyTukar,
                ];
            }

            // Fallback bila tidak ada detail terpilih
            if (empty($details)) {
                $produk = $produkList->random();
                $hargaBeli = (float) $produk->harga_beli ?: rand(1000, 200000);
                $qtyRetur = rand(1, 20);
                $subtotal = $qtyRetur * $hargaBeli;

                $details[] = [
                    'produk_id' => $produk->id,
                    'nama_barang' => $produk->nama_barang,
                    'qty_retur' => $qtyRetur,
                    'harga_beli' => $hargaBeli,
                    'subtotal' => $subtotal,
                    'produk_tukar_id' => null,
                    'nama_barang_tukar' => null,
                    'qty_tukar' => 0,
                ];
                $totalRetur = $subtotal;
            }

            $retur = ReturPembelian::create([
                'no_retur' => $noRetur,
                'pembelian_id' => $pembelian->id,
                'no_pembelian_asal' => $pembelian->no_pembelian,
                'supplier_id' => $pembelian->supplier_id,
                'nama_supplier' => $pembelian->nama_supplier,
                'tanggal' => $tanggal,
                'tipe' => $tipe,
                'alasan' => $alasanOptions[array_rand($alasanOptions)],
                'total_retur' => $totalRetur,
                'status' => $statusOptions[array_rand($statusOptions)],
                'keterangan' => rand(0, 3) === 0 ? 'Retur diterima supplier ' . $pembelian->nama_supplier : null,
                'store_id' => $store->id,
            ]);

            foreach ($details as $detail) {
                ReturPembelianDetail::create(array_merge($detail, ['retur_pembelian_id' => $retur->id]));
            }

            $baru++;
        }

        $this->command?->info("Retur Pembelian: {$baru} baru dibuat.");
    }

    /**
     * Nomor retur unik per store + tanggal + urutan.
     * Karena seeder idempotent, urutan dihitung dari data existing.
     */
    protected function generateNoRetur(string $kodeToko, string $tanggal, int $fallback): string
    {
        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $kodeToko));
        $prefix = 'RT' . $kode . str_replace('-', '', $tanggal);

        $last = ReturPembelian::where('no_retur', 'like', $prefix . '%')
            ->orderByDesc('no_retur')
            ->value('no_retur');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : $fallback;

        return sprintf('%s%04d', $prefix, $next);
    }
}
