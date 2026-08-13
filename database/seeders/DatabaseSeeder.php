<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Idempotent: aman dijalankan berulang kali
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        $this->call([
            RolePermissionSeeder::class,
            AnggotaSeeder::class,
            ProdukSupplierSeeder::class,
            VoucherSeeder::class,
            PembelianSeeder::class,
            TerimaBarangSeeder::class,
            ReturPembelianSeeder::class,
            KirimBarangSeeder::class,
        ]);
    }
}
