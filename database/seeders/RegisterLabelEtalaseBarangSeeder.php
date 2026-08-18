<?php

namespace Database\Seeders;

use App\Models\Produk;
use App\Models\RegisterLabelEtalaseBarang;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RegisterLabelEtalaseBarangSeeder extends Seeder
{
    public function run(): void
    {
        $storeIds = Store::pluck('id')->all();
        if (empty($storeIds)) {
            $storeIds = [null];
        }

        $produks = Produk::query()->orderBy('id')->get(['id', 'kode_barang', 'nama_barang', 'kategori', 'satuan', 'no_rak', 'harga_jual']);
        if ($produks->isEmpty()) {
            $this->command?->warn('Tidak ada produk. Jalankan ProdukSupplierSeeder dulu.');
            return;
        }

        $userIds = User::pluck('id')->all();
        if (empty($userIds)) {
            $userIds = [null];
        }

        $ukuranOptions = ['kecil', 'sedang', 'besar'];
        $statusOptions = ['draft', 'tercetak', 'dipasang', 'batal'];

        $now = Carbon::now();
        $start = Carbon::createFromFormat('Y-m-d', '2024-02-01');
        $totalDays = $start->diffInDays($now);

        $counter = 0;
        $created = 0;

        foreach ($produks as $produk) {
            $storeId = $storeIds[array_rand($storeIds)];
            $userId = $userIds[array_rand($userIds)];

            $tgl = $start->copy()->addDays(rand(0, $totalDays));

            $kodeToko = $this->storeKode($storeId);
            $kodeTokoShort = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $kodeToko));
            if ($kodeTokoShort === '') {
                $kodeTokoShort = 'PUSAT';
            }

            $prefix = 'RLE' . $kodeTokoShort . $tgl->format('Ymd');
            $counter++;
            $noTransaksi = $prefix . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
            $noBukti = 'BLE' . $kodeTokoShort . $tgl->format('Ymd') . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);

            $jumlahLabel = rand(1, 20);
            $ukuran = $ukuranOptions[array_rand($ukuranOptions)];
            $status = $statusOptions[array_rand($statusOptions)];

            RegisterLabelEtalaseBarang::updateOrCreate(
                ['no_transaksi' => $noTransaksi],
                [
                    'tgl_register' => $tgl->toDateString(),
                    'no_bukti' => $noBukti,
                    'produk_id' => $produk->id,
                    'kode_barang' => $produk->kode_barang,
                    'nama_barang' => $produk->nama_barang,
                    'kategori' => $produk->kategori,
                    'satuan' => $produk->satuan,
                    'no_rak' => $produk->no_rak,
                    'harga_jual' => $produk->harga_jual,
                    'jumlah_label' => $jumlahLabel,
                    'ukuran_label' => $ukuran,
                    'keterangan' => 'Label etalase untuk ' . $produk->nama_barang . ' (' . $jumlahLabel . ' label, ukuran ' . $ukuran . ')',
                    'status' => $status,
                    'user_id' => $userId,
                    'store_id' => $storeId,
                ]
            );

            $created++;

            if ($created >= 100) {
                break;
            }
        }

        $this->command?->info("Register Label Etalase Barang: {$created} data dibuat/diperbarui.");
    }

    protected function storeKode($storeId): ?string
    {
        if (!$storeId) {
            return 'PUSAT';
        }
        return Store::where('id', $storeId)->value('kode') ?? 'PUSAT';
    }
}
