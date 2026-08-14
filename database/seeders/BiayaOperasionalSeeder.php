<?php

namespace Database\Seeders;

use App\Models\BiayaOperasional;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BiayaOperasionalSeeder extends Seeder
{
    /**
     * Seed 100 data biaya operasional Kopinka (idempoten via updateOrCreate).
     */
    public function run(): void
    {
        $storeIds = Store::pluck('id')->all();

        // Fallback jika belum ada store
        if (empty($storeIds)) {
            $storeIds = [null];
        }

        // Unit sumber pengeluaran (nama toko/unit)
        $units = [
            'Kopinka Pusat',
            'Kopinka 1',
            'Kopinka 2',
            'Kopinka 3',
            'Kopinka 4',
            'Kopinka 5',
            'Kantor Kasir',
            'Gudang Pusat',
        ];

        // Kategori dengan rentang nominal realistis (min, max) dalam Rupiah
        $kategori = [
            'Gaji Karyawan' => [3_000_000, 12_000_000],
            'Listrik' => [350_000, 2_500_000],
            'Air' => [80_000, 450_000],
            'Telepon & Internet' => [300_000, 1_200_000],
            'Sewa Gedung' => [2_000_000, 8_000_000],
            'ATK' => [50_000, 750_000],
            'Transportasi' => [100_000, 1_500_000],
            'Perawatan & Pemeliharaan' => [150_000, 3_000_000],
            'Promosi & Marketing' => [200_000, 2_500_000],
            'Pajak & Retribusi' => [250_000, 4_000_000],
            'Konsumsi' => [75_000, 900_000],
            'Bank & Admin' => [25_000, 500_000],
            'Lainnya' => [50_000, 1_000_000],
        ];

        $kategoriKeys = array_keys($kategori);
        $now = Carbon::now();
        $start = Carbon::createFromFormat('Y-m-d', '2024-01-01');
        $totalDays = $start->diffInDays($now);

        $counter = 0;

        for ($i = 1; $i <= 100; $i++) {
            // Tanggal tersebar merata sepanjang 2024–2025
            $tanggal = $start->copy()->addDays(rand(0, $totalDays));

            $kat = $kategoriKeys[array_rand($kategoriKeys)];
            [$min, $max] = $kategori[$kat];
            // Pembulatan ke ribuan agar rapi
            $jumlah = round(rand((int) ($min / 1000), (int) ($max / 1000)) * 1000);

            $unit = $units[array_rand($units)];
            $storeId = $storeIds[array_rand($storeIds)];

            // No bukti unik: OP-KODE-YYYYMMDD-XXXX
            $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $unit));
            if ($kode === '') {
                $kode = 'PUSAT';
            }
            $tanggalStr = $tanggal->format('Ymd');
            $counter++;
            $noBukti = 'OP' . $kode . $tanggalStr . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);

            $keteranganMap = [
                'Gaji Karyawan' => ['Gaji bulanan', 'THR', 'Bonus kinerja', 'Gaji mingguan'],
                'Listrik' => ['Tagihan PLN', 'Token listrik', 'Rekening listrik'],
                'Air' => ['Tagihan PDAM', 'Air galon kantor'],
                'Telepon & Internet' => ['Internet kantor', 'Pulsa & kuota', 'Telepon fixed'],
                'Sewa Gedung' => ['Sewa ruko', 'Sewa gudang'],
                'ATK' => ['Pembelian stationery', 'Kertas & tinta', 'Alat tulis'],
                'Transportasi' => ['BBM operasional', 'Ongkos kirim', 'Transportasi staff'],
                'Perawatan & Pemeliharaan' => ['Servis AC', 'Perbaikan etalase', 'Cat toko'],
                'Promosi & Marketing' => ['Spanduk', 'Banner sosmed', 'Bagi brosur'],
                'Pajak & Retribusi' => ['PBB', 'Retribusi daerah', 'Pajak daerah'],
                'Konsumsi' => ['Konsumsi rapat', 'Snack karyawan', 'Air minum'],
                'Bank & Admin' => ['Admin bank', 'Biaya transfer', 'Cek Giro'],
                'Lainnya' => ['Denda', 'Iuran lingkungan', 'Keperluan lain'],
            ];

            $ket = $keteranganMap[$kat][array_rand($keteranganMap[$kat])];

            BiayaOperasional::updateOrCreate(
                ['no_bukti' => $noBukti],
                [
                    'tanggal' => $tanggal->toDateString(),
                    'dari_unit' => $unit,
                    'kategori' => $kat,
                    'keterangan' => $ket,
                    'jumlah' => $jumlah,
                    'store_id' => $storeId,
                    'created_at' => $tanggal,
                    'updated_at' => $tanggal,
                ]
            );
        }
    }
}
