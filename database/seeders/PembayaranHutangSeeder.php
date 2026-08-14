<?php

namespace Database\Seeders;

use App\Models\PembayaranHutang;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PembayaranHutangSeeder extends Seeder
{
    /**
     * Seed 100 data pembayaran hutang supplier (idempoten via updateOrCreate).
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

        $userIds = User::pluck('id')->all();
        if (empty($userIds)) {
            $userIds = [null];
        }

        $now = Carbon::now();
        $start = Carbon::createFromFormat('Y-m-d', '2024-01-01');
        $totalDays = $start->diffInDays($now);

        $counter = 0;

        for ($i = 1; $i <= 100; $i++) {
            $supplier = $suppliers->random();
            $storeId = $storeIds[array_rand($storeIds)];
            $userId = $userIds[array_rand($userIds)];

            // Tanggal pembelian (hutang awal) tersebar 2024-2025
            $tglPembelian = $start->copy()->addDays(rand(0, $totalDays));
            // Jatuh tempo 14-60 hari setelah pembelian
            $tglJatuhTempo = $tglPembelian->copy()->addDays(rand(14, 60));
            // Tanggal bayar antara pembelian dan sekarang
            $tglBayar = $tglPembelian->copy()->addDays(rand(1, min(90, $tglPembelian->diffInDays($now))));
            if ($tglBayar->gt($now)) {
                $tglBayar = $now->copy();
            }

            $kodeToko = $this->storeKode($storeId);
            $kodeTokoShort = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $kodeToko));
            if ($kodeTokoShort === '') {
                $kodeTokoShort = 'PUSAT';
            }

            // No transaksi unik: PH-KODE-YYYYMMDD-XXXX
            $prefix = 'PH' . $kodeTokoShort . $tglBayar->format('Ymd');
            $counter++;
            $noTransaksi = $prefix . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
            $noBukti = 'BK' . $kodeTokoShort . $tglBayar->format('Ymd') . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
            $noFaktur = 'INV-' . strtoupper(substr(md5($noTransaksi), 0, 8));

            // Nilai pembelian realistis
            $nilaiPembelian = round(rand(2_000_000, 50_000_000) / 1000) * 1000;
            // Retur 0 - 15% dari nilai
            $returPembelian = round(($nilaiPembelian * (rand(0, 15) / 100)) / 1000) * 1000;
            // Diskon pembayaran 0 - 10% dari (nilai - retur)
            $setelahRetur = max(0, $nilaiPembelian - $returPembelian);
            $diskonPembayaran = round(($setelahRetur * (rand(0, 10) / 100)) / 1000) * 1000;
            $totalHarusDibayar = max(0, $setelahRetur - $diskonPembayaran);

            // Total terbayar sebelumnya (akumulasi) — sebagian atau lunas
            $persenTerbayar = [0.3, 0.5, 0.7, 1.0][array_rand([0.3, 0.5, 0.7, 1.0])];
            $totalTerbayar = round(($totalHarusDibayar * $persenTerbayar) / 1000) * 1000;
            if ($totalTerbayar > $totalHarusDibayar) {
                $totalTerbayar = $totalHarusDibayar;
            }
            $totalDiskon = $diskonPembayaran;

            // Jumlah bayar pada transaksi ini (<= sisa)
            $sisaSebelum = max(0, $totalHarusDibayar - $totalTerbayar);
            $jumlahBayar = round(($sisaSebelum * (rand(50, 100) / 100)) / 1000) * 1000;
            if ($jumlahBayar > $sisaSebelum) {
                $jumlahBayar = $sisaSebelum;
            }

            $totalTerbayarAkhir = min($totalHarusDibayar, $totalTerbayar + $jumlahBayar);
            $kurangBayar = max(0, $totalHarusDibayar - $totalTerbayarAkhir);
            $sisaHutang = $kurangBayar;

            PembayaranHutang::updateOrCreate(
                ['no_transaksi' => $noTransaksi],
                [
                    'tgl_pembelian' => $tglPembelian->toDateString(),
                    'tgl_jatuh_tempo' => $tglJatuhTempo->toDateString(),
                    'no_faktur' => $noFaktur,
                    'kode_supplier' => $supplier->kode,
                    'nama_supplier' => $supplier->nama,
                    'alamat' => $supplier->alamat,
                    'no_telp' => $supplier->no_telp,
                    'contact_person' => $supplier->contact_person,
                    'no_bukti' => $noBukti,
                    'tanggal_bayar' => $tglBayar->toDateString(),
                    'nilai_pembelian' => $nilaiPembelian,
                    'retur_pembelian' => $returPembelian,
                    'diskon_pembayaran' => $diskonPembayaran,
                    'total_harus_dibayar' => $totalHarusDibayar,
                    'jumlah_bayar' => $jumlahBayar,
                    'total_terbayar' => $totalTerbayarAkhir,
                    'total_diskon' => $totalDiskon,
                    'kurang_bayar' => $kurangBayar,
                    'sisa_hutang' => $sisaHutang,
                    'supplier_id' => $supplier->id,
                    'user_id' => $userId,
                    'store_id' => $storeId,
                ]
            );
        }

        $this->command?->info('Pembayaran Hutang: 100 data dibuat/diperbarui.');
    }

    protected function storeKode($storeId): ?string
    {
        if (!$storeId) {
            return 'PUSAT';
        }
        return Store::where('id', $storeId)->value('kode') ?? 'PUSAT';
    }
}
