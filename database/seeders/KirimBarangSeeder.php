<?php

namespace Database\Seeders;

use App\Models\KirimBarang;
use App\Models\KirimBarangDetail;
use App\Models\Produk;
use App\Models\Stok;
use App\Models\Store;
use App\Models\StokRiwayat;
use Illuminate\Database\Seeder;

class KirimBarangSeeder extends Seeder
{
    /**
     * Seed 100 kiriman barang antar toko (idempotent, aman dijalankan ulang).
     *
     * Setiap kiriman:
     * - asal ≠ tujuan toko
     * - 1-5 produk acak, qty 1-30
     * - memutasi stok: kurangi toko asal, tambah toko tujuan (via tabel stok)
     * - mencatat StokRiwayat (keluar di asal, masuk di tujuan)
     */
    public function run(): void
    {
        $count = 100;

        $stores = Store::query()->orderBy('id')->get();
        if ($stores->count() < 2) {
            $this->command?->warn('Butuh minimal 2 toko untuk seeder KirimBarang.');

            return;
        }

        $produkIds = Produk::query()->orderBy('id')->pluck('id')->all();
        if (empty($produkIds)) {
            $this->command?->warn('Tidak ada produk untuk seeder KirimBarang.');

            return;
        }

        $baru = 0;
        for ($i = 1; $i <= $count; $i++) {
            $noKirim = 'KBSEED' . sprintf('%05d', $i);

            if (KirimBarang::where('no_kirim', $noKirim)->exists()) {
                continue;
            }

            // Pilih toko asal & tujuan (berbeda)
            $asal = $stores->random();
            do {
                $tujuan = $stores->random();
            } while ($tujuan->id === $asal->id);

            $tanggal = now()->subDays(rand(0, 120))->toDateString();

            $jumlahItem = rand(1, 5);
            $produkTerpilih = (array) array_rand(array_flip($produkIds), $jumlahItem);

            $details = [];
            $totalItem = 0;
            $totalNilai = 0;

            foreach ($produkTerpilih as $pid) {
                $produk = Produk::find($pid);
                if (! $produk) {
                    continue;
                }

                $qty = rand(1, 30);
                $hargaBeli = (float) $produk->harga_beli;
                $subtotal = $qty * $hargaBeli;

                $details[] = [
                    'produk' => $produk,
                    'qty' => $qty,
                    'harga_beli' => $hargaBeli,
                    'subtotal' => $subtotal,
                ];

                $totalItem += $qty;
                $totalNilai += $subtotal;
            }

            if (empty($details)) {
                continue;
            }

            $kirim = KirimBarang::create([
                'no_kirim' => $noKirim,
                'tanggal' => $tanggal,
                'store_asal_id' => $asal->id,
                'nama_store_asal' => $asal->nama,
                'store_tujuan_id' => $tujuan->id,
                'nama_store_tujuan' => $tujuan->nama,
                'status' => 'selesai',
                'total_item' => $totalItem,
                'total_nilai' => $totalNilai,
                'keterangan' => 'Seeder mutasi stok antar toko.',
                'store_id' => $asal->id,
            ]);

            $qtyById = [];
            foreach ($details as $d) {
                KirimBarangDetail::create([
                    'kirim_barang_id' => $kirim->id,
                    'produk_id' => $d['produk']->id,
                    'nama_barang' => $d['produk']->nama_barang,
                    'qty' => $d['qty'],
                    'harga_beli' => $d['harga_beli'],
                    'subtotal' => $d['subtotal'],
                ]);
                $qtyById[$d['produk']->id] = $d['qty'];
            }

            // Mutasi stok per toko + riwayat
            $this->mutasiStok($kirim, $qtyById, $tanggal);

            $baru++;
        }

        $this->command?->info("KirimBarang: {$baru} baru dibuat.");
    }

    /**
     * Kurangi stok toko asal, tambah stok toko tujuan, catat StokRiwayat.
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

            // Stok asal (tidak boleh negatif)
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

            // Stok tujuan
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
}
