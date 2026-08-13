<?php

namespace Database\Seeders;

use App\Models\Produk;
use App\Models\Store;
use App\Models\StokOpname;
use App\Models\StokOpnameDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StokOpnameSeeder extends Seeder
{
    /**
     * Seed 100 Stok Opname records — idempoten.
     *
     * - Tersebar di 6 store (pusat + K1..K5).
     * - 1–6 produk per opname, stok_sistem = stok riil saat itu.
     * - stok_fisik = sistem ± acak (untuk simulasi selisih).
     * - Status campuran (sebagian 'selesai' → sudah reconcile).
     */
    public function run(): void
    {
        $stores = Store::query()->orderBy('id')->get();
        if ($stores->isEmpty()) {
            $this->command?->warn('Tidak ada store. Jalankan RolePermissionSeeder dulu.');
            return;
        }

        $produkList = Produk::query()->orderBy('id')->get(['id', 'nama_barang']);
        if ($produkList->isEmpty()) {
            $this->command?->warn('Tidak ada produk. Jalankan ProdukSupplierSeeder dulu.');
            return;
        }

        $statusOptions = ['draft', 'selesai', 'selesai', 'batal'];

        $baru = 0;

        for ($i = 1; $i <= 100; $i++) {
            $store = $stores[($i - 1) % $stores->count()];
            $tanggal = now()->subDays((int) (($i * 7) % 180))->toDateString();

            $noOpname = $this->generateNoOpname($store->kode, $tanggal, $i);

            // Skip bila sudah ada (idempoten)
            if (StokOpname::where('no_opname', $noOpname)->exists()) {
                continue;
            }

            $status = $statusOptions[array_rand($statusOptions)];
            $petugas = fake()->name();
            $keterangan = rand(0, 3) === 0 ? fake()->sentence() : null;

            $opname = StokOpname::create([
                'no_opname' => $noOpname,
                'tanggal' => $tanggal,
                'status' => $status,
                'petugas' => $petugas,
                'keterangan' => $keterangan,
                'store_id' => $store->id,
            ]);

            $jumlahItem = rand(1, 6);
            $pickedProduks = $produkList->random(min($jumlahItem, $produkList->count()));

            $reconcile = $status === 'selesai';
            $storeIdFinal = $store->id;

            foreach ($pickedProduks as $produk) {
                $stokSistem = $produk->stokDi($storeIdFinal);
                $selisih = rand(-20, 20); // Selisih acak
                $stokFisik = $stokSistem + $selisih;
                if ($stokFisik < 0) $stokFisik = 0; // Stok fisik tidak boleh negatif

                StokOpnameDetail::create([
                    'stok_opname_id' => $opname->id,
                    'produk_id' => $produk->id,
                    'nama_barang' => $produk->nama_barang,
                    'stok_sistem' => $stokSistem,
                    'stok_fisik' => $stokFisik,
                    'selisih' => $selisih,
                ]);

                // Reconcile stok riil jika status selesai
                if ($reconcile && $selisih !== 0) {
                    if ($selisih > 0) {
                        $produk->tambahStok($selisih, "Opname {$noOpname}", $storeIdFinal);
                    } else {
                        $produk->kurangiStok(abs($selisih), "Opname {$noOpname}", $storeIdFinal);
                    }
                }
            }
            $baru++;
        }
        $this->command?->info("Stok Opname: {$baru} baru dibuat.");
    }

    /**
     * Nomor opname unik per store + tanggal + urutan.
     */
    protected function generateNoOpname(string $kodeToko, string $tanggal, int $fallback): string
    {
        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $kodeToko));
        if ($kode === '') {
            $kode = 'PUSAT';
        }
        $prefix = 'SO' . $kode . str_replace('-', '', $tanggal);

        $last = StokOpname::where('no_opname', 'like', $prefix . '%')
            ->orderByDesc('no_opname')
            ->value('no_opname');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : $fallback;

        return sprintf('%s%04d', $prefix, $next);
    }
}
