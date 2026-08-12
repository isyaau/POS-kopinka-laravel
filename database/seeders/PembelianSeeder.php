<?php

namespace Database\Seeders;

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Produk;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class PembelianSeeder extends Seeder
{
    /**
     * Seed 100 pembelian dari supplier (idempotent, aman dijalankan ulang).
     *
     * - Tersebar di 6 store (pusat + K1..K5).
     * - Memakai supplier & produk yang sudah ada.
     * - Setiap pembelian: 1-4 item, qty 5-50, harga beli acak, diskon, PPN,
     *   jenis bayar (tunai / kredit), tgl expired acak.
     */
    public function run(): void
    {
        $stores = Store::query()->orderBy('id')->get();
        if ($stores->isEmpty()) {
            $this->command?->warn('Tidak ada store. Jalankan RolePermissionSeeder dulu.');

            return;
        }

        $suppliers = Supplier::query()->orderBy('id')->pluck('id');
        if ($suppliers->isEmpty()) {
            $this->command?->warn('Tidak ada supplier. Jalankan ProdukSupplierSeeder dulu.');

            return;
        }

        $produkList = Produk::query()->orderBy('id')->get(['id', 'nama_barang', 'harga_beli', 'harga_jual']);
        if ($produkList->isEmpty()) {
            $this->command?->warn('Tidak ada produk. Jalankan ProdukSupplierSeeder dulu.');

            return;
        }

        $jenisBayarOptions = ['tunai', 'kredit'];
        $statusOptions = ['selesai', 'selesai', 'selesai', 'draft', 'batal'];

        $baru = 0;

        for ($i = 1; $i <= 100; $i++) {
            // Pilih store secara round-robin (idempotent: nomor unik per store+tanggal)
            $store = $stores[($i - 1) % $stores->count()];
            // Tanggal deterministik agar seeder idempotent saat dijalankan ulang
            $tanggal = now()->subDays((int) (($i * 7) % 91))->toDateString();

            $noPembelian = $this->generateNoPembelian($store->kode, $tanggal, $i);

            // Skip bila sudah ada (idempotent)
            if (Pembelian::where('no_pembelian', $noPembelian)->exists()) {
                continue;
            }

            $supplierId = $suppliers[($i - 1) % $suppliers->count()];
            $supplier = Supplier::find($supplierId);

            $jumlahItem = rand(1, 4);
            $picked = $produkList->random(min($jumlahItem, $produkList->count()));

            $nilai = 0;
            $details = [];

            foreach ($picked as $produk) {
                $qty = rand(5, 50);
                $hargaBeli = $produk->harga_beli > 0 ? $produk->harga_beli : rand(1000, 200000);
                $hargaJual = $produk->harga_jual > 0 ? $produk->harga_jual : round($hargaBeli * 1.25, -2);
                $diskonItem = rand(0, 4) === 0 ? rand(100, 2000) : 0;
                $subtotalItem = max(0, $qty * ($hargaBeli - $diskonItem));
                $nilai += $subtotalItem;

                $details[] = [
                    'produk_id' => $produk->id,
                    'nama_barang' => $produk->nama_barang,
                    'qty' => $qty,
                    'harga_beli' => $hargaBeli,
                    'diskon_item' => $diskonItem,
                    'subtotal' => $subtotalItem,
                    'harga_jual' => $hargaJual,
                    'tanggal_expired' => rand(0, 3) === 0 ? now()->addMonths(rand(1, 24))->toDateString() : null,
                ];
            }

            $diskon = rand(0, 3) === 0 ? rand(5000, 50000) : 0;
            $subtotal = max(0, $nilai - $diskon);

            $terlampirBuktiPpn = rand(0, 1) === 1;
            $hargaJualTermasukPpn = rand(0, 1) === 1;

            // PPN masukan 11% dari subtotal (bila bukti PPN terlampir)
            $ppnMasukan = $terlampirBuktiPpn ? round($subtotal * 0.11, 2) : 0;

            $total = $hargaJualTermasukPpn ? $subtotal : $subtotal + $ppnMasukan;

            $jenisBayar = $jenisBayarOptions[array_rand($jenisBayarOptions)];

            $sisaHutang = match ($jenisBayar) {
                'kredit' => $total,
                default => 0,
            };

            $pembelian = Pembelian::create([
                'no_pembelian' => $noPembelian,
                'no_faktur' => 'INV-' . strtoupper(substr(md5($noPembelian), 0, 8)),
                'supplier_id' => $supplierId,
                'nama_supplier' => $supplier?->nama,
                'tanggal' => $tanggal,
                'terlampir_bukti_ppn' => $terlampirBuktiPpn,
                'harga_jual_termasuk_ppn' => $hargaJualTermasukPpn,
                'nilai' => $nilai,
                'diskon' => $diskon,
                'subtotal' => $subtotal,
                'ppn_masukan' => $ppnMasukan,
                'total' => $total,
                'jenis_bayar' => $jenisBayar,
                'sisa_hutang' => $sisaHutang,
                'status' => $statusOptions[array_rand($statusOptions)],
                'keterangan' => rand(0, 3) === 0 ? 'Pembelian stok rutin ' . $supplier?->nama : null,
                'store_id' => $store->id,
            ]);

            foreach ($details as $detail) {
                PembelianDetail::create(array_merge($detail, ['pembelian_id' => $pembelian->id]));
            }

            $baru++;
        }

        $this->command?->info("Pembelian: {$baru} baru dibuat.");
    }

    /**
     * Nomor pembelian unik per store + tanggal + urutan.
     * Karena seeder idempotent, urutan dihitung dari data existing.
     */
    protected function generateNoPembelian(string $kodeToko, string $tanggal, int $fallback): string
    {
        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $kodeToko));
        $prefix = 'PB' . $kode . str_replace('-', '', $tanggal);

        $last = Pembelian::where('no_pembelian', 'like', $prefix . '%')
            ->orderByDesc('no_pembelian')
            ->value('no_pembelian');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : $fallback;

        return sprintf('%s%04d', $prefix, $next);
    }
}
