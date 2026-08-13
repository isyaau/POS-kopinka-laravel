<?php

namespace Database\Seeders;

use App\Models\Produk;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\TerimaBarang;
use App\Models\TerimaBarangDetail;
use Illuminate\Database\Seeder;

class TerimaBarangSeeder extends Seeder
{
    /**
     * Seed 100 penerimaan barang (konsinyasi / retur toko) — idempoten.
     *
     * - Tersebar di 6 store (pusat + K1..K5).
     * - Supplier & produk existing.
     * - 1-4 item per penerimaan, qty 5-50.
     * - Stok per-toko ikut ditambahkan agar konsisten dengan tabel `stok`.
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

        $produkList = Produk::query()->orderBy('id')->get(['id', 'nama_barang']);
        if ($produkList->isEmpty()) {
            $this->command?->warn('Tidak ada produk. Jalankan ProdukSupplierSeeder dulu.');

            return;
        }

        $tipeOptions = ['konsinyasi', 'konsinyasi', 'konsinyasi', 'retur_toko'];
        $statusOptions = ['selesai', 'selesai', 'selesai', 'draft', 'batal'];

        $baru = 0;

        for ($i = 1; $i <= 100; $i++) {
            $store = $stores[($i - 1) % $stores->count()];
            $tanggal = now()->subDays((int) (($i * 5) % 120))->toDateString();

            $noTerima = $this->generateNoTerima($store->kode, $tanggal, $i);

            // Skip bila sudah ada (idempoten)
            if (TerimaBarang::where('no_terima', $noTerima)->exists()) {
                continue;
            }

            $supplierId = $suppliers[($i - 1) % $suppliers->count()];
            $supplier = Supplier::find($supplierId);

            $tipe = $tipeOptions[array_rand($tipeOptions)];
            $status = $statusOptions[array_rand($statusOptions)];

            $jumlahItem = rand(1, 4);
            $picked = $produkList->random(min($jumlahItem, $produkList->count()));

            $terima = TerimaBarang::create([
                'no_terima' => $noTerima,
                'no_surat_jalan' => 'SJ-' . strtoupper(substr(md5($noTerima), 0, 8)),
                'tipe' => $tipe,
                'supplier_id' => $supplierId,
                'nama_supplier' => $supplier?->nama,
                'tanggal' => $tanggal,
                'status' => $status,
                'keterangan' => rand(0, 3) === 0
                    ? ($tipe === 'konsinyasi' ? 'Titipan konsinyasi ' . $supplier?->nama : 'Retur barang dari toko')
                    : null,
                'store_id' => $store->id,
            ]);

            foreach ($picked as $produk) {
                $qty = rand(5, 50);
                $tanggalExpired = rand(0, 3) === 0 ? now()->addMonths(rand(1, 24))->toDateString() : null;

                TerimaBarangDetail::create([
                    'terima_barang_id' => $terima->id,
                    'produk_id' => $produk->id,
                    'nama_barang' => $produk->nama_barang,
                    'qty' => $qty,
                    'tanggal_expired' => $tanggalExpired,
                ]);

                // Stok masuk ke tabel stok per-toko (konsisten mutasi).
                $produk->tambahStok($qty, "Terima barang {$noTerima}", $store->id);
            }

            $baru++;
        }

        $this->command?->info("Terima Barang: {$baru} baru dibuat.");
    }

    /**
     * Nomor terima unik per store + tanggal + urutan.
     */
    protected function generateNoTerima(string $kodeToko, string $tanggal, int $fallback): string
    {
        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $kodeToko));
        if ($kode === '') {
            $kode = 'PUSAT';
        }
        $prefix = 'TB' . $kode . str_replace('-', '', $tanggal);

        $last = TerimaBarang::where('no_terima', 'like', $prefix . '%')
            ->orderByDesc('no_terima')
            ->value('no_terima');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : $fallback;

        return sprintf('%s%04d', $prefix, $next);
    }
}
