<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AnggotaSeeder extends Seeder
{
    /**
     * Seed 100 data anggota & karyawan Kopinka.
     */
    public function run(): void
    {
        $storeIds = Store::pluck('id')->all();

        // Fallback jika belum ada store
        if (empty($storeIds)) {
            $storeIds = [null];
        }

        $namaDepan = [
            'Agus', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fitri', 'Gunawan', 'Hesti', 'Indra', 'Joko',
            'Kartika', 'Lukman', 'Maya', 'Nugroho', 'Oktavia', 'Putra', 'Ratna', 'Slamet', 'Tuti', 'Umar',
            'Vina', 'Wahyudi', 'Yuni', 'Zainal', 'Andi', 'Bambang', 'Cahyo', 'Dian', 'Eka', 'Fajar',
            'Gilang', 'Hendra', 'Intan', 'Jihan', 'Kurnia', 'Lestari', 'Miftah', 'Nadia', 'Oman', 'Puji',
            'Rahmat', 'Sari', 'Taufik', 'Utami', 'Wawan', 'Yanti', 'Arif', 'Bayu', 'Chairul', 'Dwi',
        ];

        $namaBelakang = [
            'Pratama', 'Santoso', 'Wijaya', 'Saputra', 'Hidayat', 'Nugraha', 'Kusuma', 'Utomo',
            'Ramadhan', 'Firmansyah', 'Suryadi', 'Maulana', 'Permata', 'Anggraini', 'Lestari',
            'Hartono', 'Setiawan', 'Irawan', 'Wibowo', 'Susanto', 'Halim', 'Gunawan', 'Rahayu',
            'Mulyani', 'Handayani', 'Sari', 'Puspita', 'Melati', 'Ayu', 'Dewi', 'Rahmawati', 'Fitriani',
            'Salsabila', 'Putri', 'Kurniawan', 'Aditya', 'Prakoso', 'Nugroho', 'Subekti', 'Purnomo',
        ];

        $divisi = ['Keuangan', 'Operasional', 'SDM', 'IT', 'Marketing', 'Kasir', 'Gudang', 'Audit'];
        $pendidikan = ['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2'];
        $kota = [
            'Jakarta', 'Bandung', 'Surabaya', 'Semarang', 'Yogyakarta', 'Medan', 'Makassar',
            'Palembang', 'Denpasar', 'Malang', 'Bekasi', 'Depok', 'Tangerang', 'Bogor', 'Solo',
        ];
        $jalan = ['Jl. Merdeka', 'Jl. Sudirman', 'Jl. Ahmad Yani', 'Jl. Diponegoro', 'Jl. Gatot Subroto', 'Jl. Pahlawan', 'Jl. Kartini', 'Jl. Dipatiukur'];

        $now = Carbon::now();

        for ($i = 1; $i <= 100; $i++) {
            $status = $i % 4 === 0 ? 'karyawan' : 'anggota'; // ±25% karyawan
            $jenisKelamin = $i % 2 === 0 ? 'P' : 'L';

            $nama = $namaDepan[array_rand($namaDepan)] . ' ' . $namaBelakang[array_rand($namaBelakang)];

            // NIP unik: ANG-XXXX / KRY-XXXX
            $nip = ($status === 'karyawan' ? 'KRY-' : 'ANG-') . str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            $tglTerdaftar = Carbon::createFromFormat('Y-m-d', '2021-01-01')
                ->addDays(rand(0, $now->diffInDays(Carbon::createFromFormat('Y-m-d', '2021-01-01'))));

            // 15% purna, jika purna maka tgl pensiun diisi & status non-aktif
            $purna = rand(1, 100) <= 15;
            $tglPensiun = $purna ? $tglTerdaftar->copy()->addYears(rand(10, 25)) : null;

            $statusAktif = ! $purna;
            $statusLimit = rand(1, 100) <= 30;

            // updateOrCreate: aman dijalankan berulang kali (idempotent)
            Anggota::updateOrCreate(
                ['nip' => $nip],
                [
                    'status' => $status,
                    'nama' => $nama,
                    'alamat' => $jalan[array_rand($jalan)] . ' No. ' . rand(1, 200) . ', ' . $kota[array_rand($kota)],
                    'tempat_lahir' => $kota[array_rand($kota)],
                    'tanggal_lahir' => Carbon::createFromFormat('Y-m-d', '1965-01-01')->addYears(rand(18, 55))->addDays(rand(0, 364)),
                    'jenis_kelamin' => $jenisKelamin,
                    'pendidikan' => $pendidikan[array_rand($pendidikan)],
                    'no_hp' => '08' . rand(11, 99) . str_pad((string) rand(0, 99999999), 8, '0', STR_PAD_LEFT),
                    'divisi_pekerjaan' => $divisi[array_rand($divisi)],
                    'status_purna' => $purna,
                    'tgl_terdaftar' => $tglTerdaftar->toDateString(),
                    'tgl_pensiun' => $tglPensiun?->toDateString(),
                    'simpanan_pokok' => 500000,
                    'simpanan_wajib' => rand(50000, 200000),
                    'status_aktif' => $statusAktif,
                    'status_limit' => $statusLimit,
                    'limit_transaksi' => $statusLimit ? rand(5, 50) * 100000 : null,
                    'store_id' => $storeIds[array_rand($storeIds)],
                    'created_at' => $tglTerdaftar,
                    'updated_at' => $tglTerdaftar,
                ]
            );
        }
    }
}
