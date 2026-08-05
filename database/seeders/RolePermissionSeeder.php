<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed roles, permissions, stores, and default users.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ===== 5 Permission =====
        $permissions = [
            'dashboard.view',
            'produk.manage',
            'transaksi.manage',
            'laporan.view',
            'user.manage',
            'anggota.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ===== Roles (prioritas admin) =====
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions); // admin = semua 5

        $kasir = Role::firstOrCreate(['name' => 'kasir']);
        $kasir->syncPermissions([
            'dashboard.view',
            'produk.manage',
            'transaksi.manage',
        ]);

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->syncPermissions($permissions); // akses penuh global via Gate::before

        // ===== 6 Stores: 1 pusat + 5 toko =====
        $stores = [
            ['kode' => 'PUSAT', 'nama' => 'Kopinka Pusat', 'tipe' => 'pusat'],
            ['kode' => 'K1', 'nama' => 'Kopinka 1', 'tipe' => 'toko'],
            ['kode' => 'K2', 'nama' => 'Kopinka 2', 'tipe' => 'toko'],
            ['kode' => 'K3', 'nama' => 'Kopinka 3', 'tipe' => 'toko'],
            ['kode' => 'K4', 'nama' => 'Kopinka 4', 'tipe' => 'toko'],
            ['kode' => 'K5', 'nama' => 'Kopinka 5', 'tipe' => 'toko'],
        ];

        foreach ($stores as $store) {
            Store::firstOrCreate(
                ['kode' => $store['kode']],
                $store,
            );
        }

        $pusat = Store::where('kode', 'PUSAT')->first();
        $k1 = Store::where('kode', 'K1')->first();

        // ===== Default users =====
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@kopinka.test'],
            [
                'name' => 'Admin Pusat',
                'password' => Hash::make('admin123'),
                'store_id' => $pusat->id,
            ],
        );
        $adminUser->assignRole('admin');

        $superUser = User::firstOrCreate(
            ['email' => 'super-admin@kopinka.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
                'store_id' => $pusat->id,
            ],
        );
        $superUser->assignRole('super-admin');

        $kasirUser = User::firstOrCreate(
            ['email' => 'kasir@kopinka.test'],
            [
                'name' => 'Kasir Kopinka 1',
                'password' => Hash::make('admin123'),
                'store_id' => $k1->id,
            ],
        );
        $kasirUser->assignRole('kasir');
    }
}
