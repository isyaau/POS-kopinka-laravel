<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\PengembalianLebihBayarPotongGaji;
use App\Models\PenerimaanAngsuranPotongGaji;
use App\Models\RegisterTagihanPiutang;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PengembalianLebihBayarPotongGajiSeeder extends Seeder
{
    public function run(): void
    {
        $storeIds = Store::pluck('id')->all();
        if (empty($storeIds)) {
            $storeIds = [null];
        }

        $potongGajis = PenerimaanAngsuranPotongGaji::query()->orderBy('id')->get(['id', 'no_transaksi', 'register_tagihan_id', 'anggota_id', 'jumlah_potong', 'total_terbayar_sesudah', 'sisa_piutang']);
        if ($potongGajis->isEmpty()) {
            $this->command?->warn('Tidak ada penerimaan angsuran potong gaji. Jalankan PenerimaanAngsuranPotongGajiSeeder dulu.');
            return;
        }

        $userIds = User::pluck('id')->all();
        if (empty($userIds)) {
            $userIds = [null];
        }

        $metodeOptions = ['transfer', 'tunai', 'potong_gaji_berikutnya'];
        $statusOptions = ['pending', 'diproses', 'selesai', 'batal'];

        $now = Carbon::now();
        $start = Carbon::createFromFormat('Y-m-d', '2024-02-01');
        $totalDays = $start->diffInDays($now);

        $counter = 0;
        $created = 0;

        foreach ($potongGajis as $pg) {
            // Buat 1-2 pengembalian per potong gaji
            $jumlahPengembalian = rand(1, 2);

            for ($j = 1; $j <= $jumlahPengembalian && $created < 100; $j++) {
                $storeId = $storeIds[array_rand($storeIds)];
                $userId = $userIds[array_rand($userIds)];

                $tgl = $start->copy()->addDays(rand(0, $totalDays));
                // Pastikan tgl pengembalian >= tgl potong gaji
                $registerTagihan = RegisterTagihanPiutang::find($pg->register_tagihan_id);
                if ($registerTagihan && $tgl < $registerTagihan->tgl_tagihan) {
                    $tgl = $registerTagihan->tgl_tagihan->copy()->addDays(rand(0, 30));
                }

                $kodeToko = $this->storeKode($storeId);
                $kodeTokoShort = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $kodeToko));
                if ($kodeTokoShort === '') {
                    $kodeTokoShort = 'PUSAT';
                }

                $prefix = 'PLB' . $kodeTokoShort . $tgl->format('Ymd');
                $counter++;
                $noTransaksi = $prefix . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
                $noBukti = 'BLB' . $kodeTokoShort . $tgl->format('Ymd') . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);

                $lebihBayar = max(1000, round(rand(5000, 50000) / 1000) * 1000);
                $metode = $metodeOptions[array_rand($metodeOptions)];
                $status = $statusOptions[array_rand($statusOptions)];

                PengembalianLebihBayarPotongGaji::updateOrCreate(
                    ['no_transaksi' => $noTransaksi],
                    [
                        'tgl_pengembalian' => $tgl->toDateString(),
                        'no_bukti' => $noBukti,
                        'register_tagihan_id' => $pg->register_tagihan_id,
                        'no_register_tagihan' => $registerTagihan?->no_transaksi,
                        'penerimaan_angsuran_potong_gaji_id' => $pg->id,
                        'no_transaksi_potong_gaji' => $pg->no_transaksi,
                        'anggota_id' => $pg->anggota_id,
                        'kode_anggota' => $pg->anggota->nip ?? '-',
                        'nama_anggota' => $pg->anggota->nama ?? '-',
                        'unit_kerja' => $registerTagihan?->unit_kerja,
                        'jabatan' => $registerTagihan?->jabatan,
                        'keterangan' => 'Pengembalian lebih bayar dari potong gaji ' . $pg->no_transaksi . ' sebesar Rp ' . number_format($lebihBayar, 0, ',', '.'),
                        'jumlah_lebih_bayar' => $lebihBayar,
                        'jumlah_pengembalian' => $lebihBayar,
                        'metode_pengembalian' => $metode,
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

            if ($created >= 100) {
                break;
            }
        }

        $this->command?->info("Pengembalian Lebih Bayar Potong Gaji: {$created} data dibuat/diperbarui.");
    }

    protected function storeKode($storeId): ?string
    {
        if (!$storeId) {
            return 'PUSAT';
        }
        return Store::where('id', $storeId)->value('kode') ?? 'PUSAT';
    }
}
