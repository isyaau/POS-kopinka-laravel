<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Voucher;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    /**
     * Seed 1000 voucher (kupon) dengan barcode & nominal.
     * Idempotent: kode VCH-0001..VCH-1000 yang sudah ada di-skip.
     */
    public function run(): void
    {
        $nominalPilihan = [10000, 20000, 25000, 50000, 75000, 100000, 150000, 200000, 250000, 500000];

        $baru = 0;
        for ($i = 1; $i <= 1000; $i++) {
            $kode = sprintf('VCH-%04d', $i);

            if (Voucher::where('kode', $kode)->exists()) {
                continue;
            }

            $nominal = $nominalPilihan[array_rand($nominalPilihan)];

            $statusRoll = rand(1, 10);
            $status = $statusRoll <= 7 ? 'aktif' : ($statusRoll <= 9 ? 'terpakai' : 'kedaluwarsa');

            $expired = null;
            if ($status === 'aktif') {
                $expired = now()->addMonths(rand(1, 12))->toDateString();
            } elseif ($status === 'kedaluwarsa') {
                $expired = now()->subMonths(rand(1, 6))->toDateString();
            }

            Voucher::create([
                'kode' => $kode,
                'nama' => 'Kupon ' . number_format($nominal, 0, ',', '.'),
                'nominal' => $nominal,
                'barcode' => (new Voucher())->generateBarcode(),
                'status' => $status,
                'tanggal_expired' => $expired,
                'keterangan' => 'Kupon hadiah nominal ' . number_format($nominal, 0, ',', '.'),
                'store_id' => $this->firstStoreId(),
            ]);
            $baru++;
        }

        $this->command?->info("Voucher: {$baru} baru dibuat.");
    }

    protected function firstStoreId(): ?int
    {
        return Store::query()->orderBy('id')->value('id');
    }
}
