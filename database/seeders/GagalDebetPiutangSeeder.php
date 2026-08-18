<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\GagalDebetPiutang;
use App\Models\RegisterTagihanPiutang;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class GagalDebetPiutangSeeder extends Seeder
{
    public function run(): void
    {
        $storeIds = Store::pluck('id')->all();
        if (empty($storeIds)) {
            $storeIds = [null];
        }

        $registerTagihans = RegisterTagihanPiutang::query()->orderBy('id')->get(['id', 'no_transaksi', 'anggota_id', 'sisa_piutang', 'tgl_tagihan']);
        if ($registerTagihans->isEmpty()) {
            $this->command?->warn('Tidak ada register tagihan. Jalankan RegisterTagihanPiutangSeeder dulu.');
            return;
        }

        $userIds = User::pluck('id')->all();
        if (empty($userIds)) {
            $userIds = [null];
        }

        $alasanGagal = [
            'Saldo tidak cukup',
            'Rekening tutup',
            'Nomor rekening salah',
            'Batas transaksi harian terlampaui',
            'Sistem bank offline',
            'Data nasabah tidak cocok',
            'Rekening diblokir',
            'Kartu ATM rusak/expired',
        ];

        $statusOptions = ['pending', 'diproses', 'selesai', 'batal'];

        $now = Carbon::now();
        $start = Carbon::createFromFormat('Y-m-d', '2024-02-01');
        $totalDays = $start->diffInDays($now);

        $counter = 0;
        $created = 0;

        foreach ($registerTagihans as $tagihan) {
            // Buat 0-2 gagal debet per register tagihan (70% kemungkinan)
            if (rand(1, 10) > 7) {
                continue;
            }
            
            $jumlahGagal = rand(1, 2);
            
            for ($j = 1; $j <= $jumlahGagal && $created < 100; $j++) {
                $storeId = $storeIds[array_rand($storeIds)];
                $userId = $userIds[array_rand($userIds)];

                $tgl = $start->copy()->addDays(rand(0, $totalDays));
                // Pastikan tgl gagal >= tgl tagihan
                if ($tgl < $tagihan->tgl_tagihan) {
                    $tgl = $tagihan->tgl_tagihan->copy()->addDays(rand(0, 60));
                }

                $kodeToko = $this->storeKode($storeId);
                $kodeTokoShort = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $kodeToko));
                if ($kodeTokoShort === '') {
                    $kodeTokoShort = 'PUSAT';
                }

                $prefix = 'GLD' . $kodeTokoShort . $tgl->format('Ymd');
                $counter++;
                $noTransaksi = $prefix . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
                $noBukti = 'BDG' . $kodeTokoShort . $tgl->format('Ymd') . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);

                $jumlahGagalDebet = rand(1, 2) === 1 ? $tagihan->sisa_piutang : round($tagihan->sisa_piutang * (rand(10, 80) / 100) / 1000) * 1000;
                $alasan = $alasanGagal[array_rand($alasanGagal)];
                $status = $statusOptions[array_rand($statusOptions)];
                $tglFollowup = $status !== 'selesai' && $status !== 'batal' 
                    ? $tgl->copy()->addDays(rand(1, 14))->toDateString() 
                    : null;

                GagalDebetPiutang::updateOrCreate(
                    ['no_transaksi' => $noTransaksi],
                    [
                        'tgl_gagal' => $tgl->toDateString(),
                        'no_bukti' => $noBukti,
                        'register_tagihan_id' => $tagihan->id,
                        'no_register_tagihan' => $tagihan->no_transaksi,
                        'anggota_id' => $tagihan->anggota_id,
                        'kode_anggota' => $tagihan->anggota->nip ?? '-',
                        'nama_anggota' => $tagihan->anggota->nama ?? '-',
                        'unit_kerja' => $tagihan->unit_kerja,
                        'jabatan' => $tagihan->jabatan,
                        'keterangan' => 'Gagal debet ' . $alasan . ' untuk ' . $tagihan->no_transaksi . ' (percobaan ke-' . $j . ')',
                        'jumlah_gagal_debet' => max(1000, $jumlahGagalDebet),
                        'alasan_gagal' => $alasan,
                        'status' => $status,
                        'tgl_followup' => $tglFollowup,
                        'catatan_followup' => $status !== 'selesai' && $status !== 'batal' 
                            ? 'Butuh follow up ke anggota untuk perbaiki data rekening / tambah saldo' 
                            : null,
                        'user_id' => $userId,
                        'store_id' => $storeId,
                    ]
                );

                $created++;
            }
            
            if ($created >= 100) {
                break;
            }
        }

        $this->command?->info("Gagal Debet Piutang: {$created} data dibuat/diperbarui.");
    }

    protected function storeKode($storeId): ?string
    {
        if (!$storeId) {
            return 'PUSAT';
        }
        return Store::where('id', $storeId)->value('kode') ?? 'PUSAT';
    }
}
