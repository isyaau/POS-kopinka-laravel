<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\RegisterTagihanPiutang;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RegisterTagihanPiutangSeeder extends Seeder
{
    /**
     * Seed 100 register tagihan piutang dagang (potong gaji) - idempoten via updateOrCreate.
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

        $unitKerja = ['Kopinka Pusat', 'Kopinka 1', 'Kopinka 2', 'Kopinka 3', 'Kopinka 4', 'Kopinka 5', 'Bagian Keuangan', 'Bagian Operasional'];
        $jabatan = ['Karyawan', 'Staff', 'Supervisor', 'Kepala Bagian', 'Admin', 'Kasir'];

        $now = Carbon::now();
        $start = Carbon::createFromFormat('Y-m-d', '2024-01-01');
        $totalDays = $start->diffInDays($now);

        $counter = 0;

        for ($i = 1; $i <= 100; $i++) {
            $anggota = $anggotas->random();
            $storeId = $storeIds[array_rand($storeIds)];
            $userId = $userIds[array_rand($userIds)];

            $tgl = $start->copy()->addDays(rand(0, $totalDays));

            $kodeToko = $this->storeKode($storeId);
            $kodeTokoShort = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $kodeToko));
            if ($kodeTokoShort === '') {
                $kodeTokoShort = 'PUSAT';
            }

            $prefix = 'TP' . $kodeTokoShort . $tgl->format('Ymd');
            $counter++;
            $noTransaksi = $prefix . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
            $noBukti = 'BT' . $kodeTokoShort . $tgl->format('Ymd') . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);

            $nilaiPiutang = rand(1_000_000, 20_000_000);
            $returPenjualan = rand(0, 5) === 0 ? round($nilaiPiutang * (rand(1, 8) / 100), -3) : 0;
            $diskon = rand(0, 6) === 0 ? round($nilaiPiutang * (rand(1, 3) / 100), -3) : 0;
            $totalHarusDibayar = max(0, $nilaiPiutang - $returPenjualan - $diskon);

            // Potong gaji: cicilan per bulan (6 - 24 bulan)
            $tenor = [6, 12, 18, 24][array_rand([6, 12, 18, 24])];
            $jumlahPotong = round($totalHarusDibayar / $tenor / 1000) * 1000;
            $periodePotong = $tgl->copy()->format('Y-m') . ' - ' . $tgl->copy()->addMonths($tenor)->format('Y-m');

            $noFaktur = 'FAK-' . strtoupper(substr(md5($noTransaksi), 0, 8));

            RegisterTagihanPiutang::updateOrCreate(
                ['no_transaksi' => $noTransaksi],
                [
                    'tgl_tagihan' => $tgl->toDateString(),
                    'no_bukti' => $noBukti,
                    'no_faktur' => $noFaktur,
                    'anggota_id' => $anggota->id,
                    'kode_anggota' => $anggota->nip,
                    'nama_anggota' => $anggota->nama,
                    'alamat' => $anggota->alamat,
                    'no_telp' => $anggota->no_hp,
                    'contact_person' => $anggota->nama,
                    'unit_kerja' => $unitKerja[array_rand($unitKerja)],
                    'jabatan' => $jabatan[array_rand($jabatan)],
                    'keterangan' => 'Tagihan piutang dagang anggota ' . $anggota->nama . ' (potong gaji)',
                    'nilai_piutang' => $nilaiPiutang,
                    'retur_penjualan' => $returPenjualan,
                    'diskon_pembayaran' => $diskon,
                    'total_harus_dibayar' => $totalHarusDibayar,
                    'total_terbayar' => 0,
                    'total_diskon' => $diskon,
                    'sisa_piutang' => $totalHarusDibayar,
                    'periode_potong' => $periodePotong,
                    'jumlah_potong_per_bulan' => $jumlahPotong,
                    'user_id' => $userId,
                    'store_id' => $storeId,
                ]
            );
        }

        $this->command?->info('Register Tagihan Piutang: 100 data dibuat/diperbarui.');
    }

    protected function storeKode($storeId): ?string
    {
        if (!$storeId) {
            return 'PUSAT';
        }
        return Store::where('id', $storeId)->value('kode') ?? 'PUSAT';
    }
}
