<?php

namespace Database\Seeders;

use App\Models\Konsinyi;
use App\Models\KonsinyiDetail;
use App\Models\Produk;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class KonsinyiSeeder extends Seeder
{
    /**
     * Seed 100 header retur & pembayaran barang konsinyi (idempoten via updateOrCreate).
     * Setiap header memiliki 1-3 item produk (tabel detail).
     */
    public function run(): void
    {
        $storeIds = Store::pluck('id')->all();
        if (empty($storeIds)) {
            $storeIds = [null];
        }

        $suppliers = Supplier::query()->orderBy('id')->get(['id', 'kode', 'nama', 'alamat', 'no_telp', 'contact_person']);
        if ($suppliers->isEmpty()) {
            $this->command?->warn('Tidak ada supplier. Jalankan ProdukSupplierSeeder dulu.');
            return;
        }

        $produks = Produk::query()->orderBy('id')->get(['id', 'kode_barang', 'nama_barang', 'satuan', 'harga_beli', 'harga_jual']);
        if ($produks->isEmpty()) {
            $this->command?->warn('Tidak ada produk. Jalankan ProdukSupplierSeeder dulu.');
            return;
        }

        $userIds = User::pluck('id')->all();
        if (empty($userIds)) {
            $userIds = [null];
        }

        $now = Carbon::now();
        $start = Carbon::createFromFormat('Y-m-d', '2024-01-01');
        $totalDays = $start->diffInDays($now);

        $counter = 0;

        for ($i = 1; $i <= 100; $i++) {
            $jenis = ($i % 3 === 0) ? 'retur' : 'pembayaran';
            $supplier = $suppliers->random();
            $storeId = $storeIds[array_rand($storeIds)];
            $userId = $userIds[array_rand($userIds)];

            $tgl = $start->copy()->addDays(rand(0, $totalDays));

            $kodeToko = $this->storeKode($storeId);
            $kodeTokoShort = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $kodeToko));
            if ($kodeTokoShort === '') {
                $kodeTokoShort = 'PUSAT';
            }

            $prefix = 'KS' . $kodeTokoShort . $tgl->format('Ymd');
            $counter++;
            $noTransaksi = $prefix . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
            $noBukti = 'BK' . $kodeTokoShort . $tgl->format('Ymd') . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
            $noFaktur = 'INV-' . strtoupper(substr(md5($noTransaksi), 0, 8));

            $itemCount = rand(1, 3);
            $picked = $produks->random($itemCount);
            if (!is_iterable($picked)) {
                $picked = collect([$picked]);
            }

            $details = [];
            $total = 0;

            foreach ($picked as $produk) {
                $qty = rand(1, 50);
                $hargaBeli = $produk->harga_beli > 0 ? (float) $produk->harga_beli : rand(2000, 100000);
                $hargaJual = $produk->harga_jual > 0 ? (float) $produk->harga_jual : round($hargaBeli * 1.25, -2);

                $subtotal = $jenis === 'retur'
                    ? max(0, $hargaBeli * $qty)
                    : max(0, $hargaJual * $qty);

                $total += $subtotal;

                $details[] = [
                    'produk_id' => $produk->id,
                    'kode_barang' => $produk->kode_barang,
                    'nama_barang' => $produk->nama_barang,
                    'satuan' => $produk->satuan,
                    'qty' => $qty,
                    'harga_beli' => $hargaBeli,
                    'harga_jual' => $hargaJual,
                    'subtotal' => $subtotal,
                ];
            }

            $diskon = rand(0, 4) === 0 ? round($total * (rand(1, 5) / 100), -2) : 0;
            $totalAfterDiskon = max(0, $total - $diskon);

            if ($jenis === 'retur') {
                $jumlahBayar = $totalAfterDiskon;
            } else {
                $persen = [0.5, 0.75, 1.0][array_rand([0.5, 0.75, 1.0])];
                $jumlahBayar = round(($totalAfterDiskon * $persen) / 1000) * 1000;
            }
            if ($jumlahBayar > $totalAfterDiskon) {
                $jumlahBayar = $totalAfterDiskon;
            }
            $kurangBayar = max(0, $totalAfterDiskon - $jumlahBayar);

            $keterangan = $jenis === 'retur'
                ? 'Retur barang konsinyi tidak terjual'
                : 'Pembayaran konsinyi barang terjual';

            $konsinyi = Konsinyi::updateOrCreate(
                ['no_transaksi' => $noTransaksi],
                [
                    'jenis' => $jenis,
                    'tgl_transaksi' => $tgl->toDateString(),
                    'no_bukti' => $noBukti,
                    'no_faktur' => $noFaktur,
                    'supplier_id' => $supplier->id,
                    'kode_supplier' => $supplier->kode,
                    'nama_supplier' => $supplier->nama,
                    'alamat' => $supplier->alamat,
                    'no_telp' => $supplier->no_telp,
                    'contact_person' => $supplier->contact_person,
                    'diskon' => $diskon,
                    'total' => $totalAfterDiskon,
                    'jumlah_bayar' => $jumlahBayar,
                    'kurang_bayar' => $kurangBayar,
                    'keterangan' => $keterangan,
                    'user_id' => $userId,
                    'store_id' => $storeId,
                ]
            );

            // Hapus detail lama lalu buat ulang (idempoten)
            $konsinyi->details()->delete();
            foreach ($details as $d) {
                KonsinyiDetail::create(array_merge($d, ['konsinyi_id' => $konsinyi->id]));
            }
        }

        $this->command?->info('Konsinyi: 100 header (dengan detail item) dibuat/diperbarui.');
    }

    protected function storeKode($storeId): ?string
    {
        if (!$storeId) {
            return 'PUSAT';
        }
        return Store::where('id', $storeId)->value('kode') ?? 'PUSAT';
    }
}
