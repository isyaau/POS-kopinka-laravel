<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\PenerimaanAngsuran;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PenerimaanAngsuranSeeder extends Seeder
{
    /**
     * Seed 100 penerimaan angsuran piutang dagang (idempoten via updateOrCreate).
     * Menggunakan anggota & user yang sudah ada.
     */
    public function run(): void
    {
        $storeIds = Store::pluck('id')->all();
        if (empty($storeIds)) {
            $storeIds = [null];
        }

        $anggotas = Anggota::query()->orderBy('id')->get(['id', 'nip', 'nama', 'alamat', 'no_hp']);
        if ($anggotas->isEmpty()) {
            $this->command?->warn('Tidak ada anggota. Jalankan AnggotaSeeder dulu.');
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
            $anggota = $anggotas->random();
            $storeId = $storeIds[array_rand($storeIds)];
            $userId = $userIds[array_rand($userIds)];

            $tgl = $start->copy()->addDays(rand(0, $totalDays));
            $tglJatuhTempo = $tgl->copy()->addDays(rand(7, 90));

            $kodeToko = $this->storeKode($storeId);
            $kodeTokoShort = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $kodeToko));
            if ($kodeTokoShort === '') {
                $kodeTokoShort = 'PUSAT';
            }

            $prefix = 'PA' . $kodeTokoShort . $tgl->format('Ymd');
            $counter++;
            $noTransaksi = $prefix . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
            $noBukti = 'BA' . $kodeTokoShort . $tgl->format('Ymd') . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);

            $nilaiPiutang = rand(1_000_000, 25_000_000);
            $returPenjualan = rand(0, 4) === 0 ? round($nilaiPiutang * (rand(1, 10) / 100), -3) : 0;
            $diskon = rand(0, 5) === 0 ? round($nilaiPiutang * (rand(1, 3) / 100), -3) : 0;
            $totalHarusDibayar = max(0, $nilaiPiutang - $returPenjualan - $diskon);

            // Proporsi angsuran yang dibayar (20% - 100%)
            $persenDibayar = [0.2, 0.3, 0.5, 0.75, 1.0][array_rand([0.2, 0.3, 0.5, 0.75, 1.0])];
            $jumlahBayar = round(($totalHarusDibayar * $persenDibayar) / 1000) * 1000;
            if ($jumlahBayar > $totalHarusDibayar) {
                $jumlahBayar = $totalHarusDibayar;
            }
            $kurangBayar = max(0, $totalHarusDibayar - $jumlahBayar);
            $sisaPiutang = $kurangBayar;

            // Akumulasi total terbayar & diskon (log tahunan)
            $totalTerbayar = $jumlahBayar;
            $totalDiskon = $diskon;

            $noFaktur = 'FAK-' . strtoupper(substr(md5($noTransaksi), 0, 8));

            PenerimaanAngsuran::updateOrCreate(
                ['no_transaksi' => $noTransaksi],
                [
                    'tgl_transaksi' => $tgl->toDateString(),
                    'no_bukti' => $noBukti,
                    'no_faktur' => $noFaktur,
                    'anggota_id' => $anggota->id,
                    'kode_anggota' => $anggota->nip,
                    'nama_anggota' => $anggota->nama,
                    'alamat' => $anggota->alamat,
                    'no_telp' => $anggota->no_hp,
                    'contact_person' => $anggota->nama,
                    'tgl_jatuh_tempo' => $tglJatuhTempo->toDateString(),
                    'nilai_piutang' => $nilaiPiutang,
                    'retur_penjualan' => $returPenjualan,
                    'diskon_pembayaran' => $diskon,
                    'total_harus_dibayar' => $totalHarusDibayar,
                    'jumlah_bayar' => $jumlahBayar,
                    'total_terbayar' => $totalTerbayar,
                    'total_diskon' => $totalDiskon,
                    'kurang_bayar' => $kurangBayar,
                    'sisa_piutang' => $sisaPiutang,
                    'keterangan' => 'Angsuran piutang dagang anggota ' . $anggota->nama,
                    'user_id' => $userId,
                    'store_id' => $storeId,
                ]
            );
        }

        $this->command?->info('Penerimaan Angsuran: 100 data dibuat/diperbarui.');
    }

    protected function storeKode($storeId): ?string
    {
        if (!$storeId) {
            return 'PUSAT';
        }
        return Store::where('id', $storeId)->value('kode') ?? 'PUSAT';
    }
}
