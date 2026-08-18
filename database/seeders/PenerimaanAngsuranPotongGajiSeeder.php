<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\PenerimaanAngsuranPotongGaji;
use App\Models\RegisterTagihanPiutang;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PenerimaanAngsuranPotongGajiSeeder extends Seeder
{
    public function run(): void
    {
        $storeIds = Store::pluck('id')->all();
        if (empty($storeIds)) {
            $storeIds = [null];
        }

        $registerTagihans = RegisterTagihanPiutang::query()->orderBy('id')->get(['id', 'no_transaksi', 'anggota_id', 'total_harus_dibayar', 'total_terbayar', 'sisa_piutang', 'jumlah_potong_per_bulan', 'tgl_tagihan']);
        if ($registerTagihans->isEmpty()) {
            $this->command?->warn('Tidak ada register tagihan. Jalankan RegisterTagihanPiutangSeeder dulu.');
            return;
        }

        $userIds = User::pluck('id')->all();
        if (empty($userIds)) {
            $userIds = [null];
        }

        $now = Carbon::now();
        $start = Carbon::createFromFormat('Y-m-d', '2024-02-01');
        $totalDays = $start->diffInDays($now);

        $counter = 0;
        $created = 0;

        foreach ($registerTagihans as $tagihan) {
            // Buat 1-3 penerimaan per register tagihan
            $jumlahPenerimaan = rand(1, 3);
            
            for ($j = 1; $j <= $jumlahPenerimaan && $created < 100; $j++) {
                $storeId = $storeIds[array_rand($storeIds)];
                $userId = $userIds[array_rand($userIds)];

                $tgl = $start->copy()->addDays(rand(0, $totalDays));
                // Pastikan tgl transaksi >= tgl tagihan
                if ($tgl < $tagihan->tgl_tagihan) {
                    $tgl = $tagihan->tgl_tagihan->copy()->addDays(rand(0, 30));
                }

                $kodeToko = $this->storeKode($storeId);
                $kodeTokoShort = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $kodeToko));
                if ($kodeTokoShort === '') {
                    $kodeTokoShort = 'PUSAT';
                }

                $prefix = 'AGP' . $kodeTokoShort . $tgl->format('Ymd');
                $counter++;
                $noTransaksi = $prefix . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
                $noBukti = 'BAGP' . $kodeTokoShort . $tgl->format('Ymd') . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);

                $jumlahPotong = $tagihan->jumlah_potong_per_bulan ?? round($tagihan->total_harus_dibayar / 12 / 1000) * 1000;
                $totalTerbayarSebelum = $tagihan->total_terbayar;
                $totalTerbayarSesudah = min($tagihan->total_harus_dibayar, $totalTerbayarSebelum + $jumlahPotong);
                $sisaPiutang = $tagihan->total_harus_dibayar - $totalTerbayarSesudah;

                // Update tagihan untuk yang berikutnya
                $tagihan->total_terbayar = $totalTerbayarSesudah;
                $tagihan->sisa_piutang = $sisaPiutang;
                $tagihan->save();

                PenerimaanAngsuranPotongGaji::updateOrCreate(
                    ['no_transaksi' => $noTransaksi],
                    [
                        'tgl_transaksi' => $tgl->toDateString(),
                        'no_bukti' => $noBukti,
                        'register_tagihan_id' => $tagihan->id,
                        'no_register_tagihan' => $tagihan->no_transaksi,
                        'anggota_id' => $tagihan->anggota_id,
                        'kode_anggota' => $tagihan->anggota->nip ?? '-',
                        'nama_anggota' => $tagihan->anggota->nama ?? '-',
                        'unit_kerja' => $tagihan->unit_kerja,
                        'jabatan' => $tagihan->jabatan,
                        'keterangan' => 'Angsuran potong gaji bulan ke-' . $j . ' untuk ' . $tagihan->no_transaksi,
                        'jumlah_potong' => $jumlahPotong,
                        'total_terbayar_sebelum' => $totalTerbayarSebelum,
                        'total_terbayar_sesudah' => $totalTerbayarSesudah,
                        'sisa_piutang' => max(0, $sisaPiutang),
                        'periode_gaji' => $tgl->format('Y-m'),
                        'user_id' => $userId,
                        'store_id' => $storeId,
                    ]
                );

                $created++;
                
                if ($sisaPiutang <= 0) {
                    break; // sudah lunas
                }
            }
            
            if ($created >= 100) {
                break;
            }
        }

        $this->command?->info("Penerimaan Angsuran Potong Gaji: {$created} data dibuat/diperbarui.");
    }

    protected function storeKode($storeId): ?string
    {
        if (!$storeId) {
            return 'PUSAT';
        }
        return Store::where('id', $storeId)->value('kode') ?? 'PUSAT';
    }
}
