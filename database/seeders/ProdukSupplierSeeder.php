<?php

namespace Database\Seeders;

use App\Models\Produk;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ProdukSupplierSeeder extends Seeder
{
    /**
     * Seed 100 supplier & 1000 produk (idempotent, aman dijalankan ulang).
     *
     * - Supplier dibuat dengan kode SPL-0001..SPL-0100, di-skip bila sudah ada.
     * - Produk dibuat dengan kode 000001..001000, di-skip bila sudah ada.
     * - Produk terhubung ke supplier secara round-robin.
     * - store_id diisi dari toko pertama (jika ada), agar tampil di scope toko.
     */
    public function run(): void
    {
        $this->seedSuppliers(100);
        $this->seedProduk(1000);
    }

    protected function seedSuppliers(int $count): void
    {
        $namaDepan = [
            'CV', 'PT', 'UD', 'Toko', 'Distributor', 'Pabrik', 'Agen', 'Koperasi',
        ];
        $namaBadan = [
            'Berkah', 'Jaya', 'Abadi', 'Makmur', 'Sejahtera', 'Prima', 'Mulia',
            'Sentosa', 'Nusantara', 'Mandiri', 'Global', 'Sukses', 'Agung',
            'Baru', 'Maju', 'Utama', 'Bersama', 'Karya', 'Niaga', 'Raya',
        ];
        $namaAkhir = [
            'Pangan', 'Sembako', 'Elektronik', 'Fashion', 'Perkakas', 'ATK',
            'Kosmetik', 'Minuman', 'Snack', 'Otomotif', 'Bangunan', 'Frozen',
            'Farmasi', 'Peralatan', 'Distribusi', 'Logistik', 'Trading', 'Suplai',
        ];

        $baru = 0;
        for ($i = 1; $i <= $count; $i++) {
            $kode = sprintf('SPL-%04d', $i);

            // Skip jika kode sudah ada (idempotent)
            if (Supplier::where('kode', $kode)->exists()) {
                continue;
            }

            $nama = sprintf(
                '%s %s %s %d',
                $namaDepan[array_rand($namaDepan)],
                $namaBadan[array_rand($namaBadan)],
                $namaAkhir[array_rand($namaAkhir)],
                $i
            );

            Supplier::create([
                'kode' => $kode,
                'nama' => $nama,
                'alamat' => 'Jl. ' . $namaBadan[array_rand($namaBadan)] . ' No. ' . rand(1, 999) . ', '
                    . ['Jakarta', 'Bandung', 'Surabaya', 'Semarang', 'Medan', 'Makassar', 'Yogyakarta'][array_rand([
                        'Jakarta', 'Bandung', 'Surabaya', 'Semarang', 'Medan', 'Makassar', 'Yogyakarta',
                    ])],
                'contact_person' => 'Bpk/Ibu ' . $namaBadan[array_rand($namaBadan)],
                'no_telp' => '08' . rand(100000000, 999999999),
                'keterangan' => 'Supplier ' . ($i % 3 === 0 ? 'utama' : ($i % 3 === 1 ? 'cadangan' : 'musiman')),
                'store_id' => $this->firstStoreId(),
            ]);
            $baru++;
        }

        $this->command?->info("Supplier: {$baru} baru dibuat.");
    }

    protected function seedProduk(int $count): void
    {
        $kategori = [
            'Sembako', 'Minuman', 'Snack', 'Makanan', 'ATK', 'Elektronik',
            'Perkakas', 'Kosmetik', 'Fashion', 'Rumah Tangga',
        ];
        $satuan = ['pcs', 'kg', 'ltr', 'box', 'pack', 'botol', 'rim', 'lusin'];

        $kata = [
            'Beras', 'Gula', 'Minyak', 'Tepung', 'Kopi', 'Teh', 'Susu', 'Roti',
            'Mi', 'Saus', 'Kecap', 'Sabun', 'Shampo', 'Pasta Gigi', 'Detergen',
            'Pensil', 'Buku', 'Pulpen', 'Kertas', 'Baterai', 'Lampu', 'Kabel',
            'Paku', 'Cat', 'Kuas', 'Handuk', 'Kaos', 'Celana', 'Topi', 'Jaket',
            'Sepatu', 'Tas', 'Sandal', 'Baskom', 'Gelas', 'Piring', 'Sendok',
            'Panci', 'Wajan', 'Rice Cooker', 'Blender', 'Kipas', 'Setrika',
            'Kulkas', 'TV', 'HP', 'Charger', 'Headset', 'Mouse', 'Keyboard',
        ];

        $baru = 0;
        for ($i = 1; $i <= $count; $i++) {
            $kode = sprintf('%06d', $i);

            if (Produk::where('kode_barang', $kode)->exists()) {
                continue;
            }

            $kategoriPilih = $kategori[array_rand($kategori)];
            $namaBarang = sprintf(
                '%s %s %d',
                $kata[array_rand($kata)],
                ['Premium', 'Standar', 'Ekonomi', 'Pro', 'Lite', 'Deluxe', 'Max', 'Mini'][array_rand([
                    'Premium', 'Standar', 'Ekonomi', 'Pro', 'Lite', 'Deluxe', 'Max', 'Mini',
                ])],
                $i
            );

            $hargaBeli = rand(1000, 200000);
            // Harga jual = beli + margin 15-45%
            $margin = 1 + (rand(15, 45) / 100);
            $hargaJual = round($hargaBeli * $margin, -2);

            $stok = rand(0, 500);
            $stokMinimum = rand(5, 20);

            Produk::create([
                'kode_barang' => $kode,
                'nama_barang' => $namaBarang,
                'kategori' => $kategoriPilih,
                'satuan' => $satuan[array_rand($satuan)],
                'no_rak' => 'R' . strtoupper($kategoriPilih[0]) . '-' . rand(1, 12),
                'harga_beli' => $hargaBeli,
                'harga_jual' => $hargaJual,
                'diskon' => rand(0, 5) === 0 ? rand(500, 5000) : 0,
                'stok' => $stok,
                'stok_minimum' => $stokMinimum,
                'tanggal_expired' => rand(0, 3) === 0 ? now()->addMonths(rand(1, 24))->toDateString() : null,
                'ppn' => rand(0, 5) === 0 ? 11 : 0,
                'store_id' => $this->firstStoreId(),
                'supplier_id' => $this->supplierIdForIndex($i),
            ]);
            $baru++;
        }

        $this->command?->info("Produk: {$baru} baru dibuat.");
    }

    /**
     * Ambil store pertama (jika ada) — konsisten dengan scope toko aktif.
     */
    protected function firstStoreId(): ?int
    {
        return Store::query()->orderBy('id')->value('id');
    }

    /**
     * Round-robin supplier_id: produk ke-i ambil supplier (i % 100) + 1.
     * Bila supplier belum ada, buat minimal 100 dulu.
     */
    protected function supplierIdForIndex(int $index): ?int
    {
        $supplierIds = Supplier::query()->orderBy('id')->pluck('id');

        if ($supplierIds->isEmpty()) {
            return null;
        }

        $position = (($index - 1) % $supplierIds->count());

        return $supplierIds[$position];
    }
}
