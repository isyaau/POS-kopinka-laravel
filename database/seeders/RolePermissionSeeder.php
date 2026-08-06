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

        // ===== Permissions (format CRUD per modul) =====
        $permissions = [
            'dashboard.view',
            // Produk
            'produk.view', 'produk.create', 'produk.update', 'produk.delete',
            'produk.export', 'produk.import',
            // Transaksi
            'transaksi.view', 'transaksi.create', 'transaksi.update', 'transaksi.delete',
            // Laporan
            'laporan.view',
            // Anggota
            'anggota.view', 'anggota.create', 'anggota.update', 'anggota.delete',
            'anggota.export', 'anggota.import',
            // Supplier
            'supplier.view', 'supplier.create', 'supplier.update', 'supplier.delete',
            'supplier.export', 'supplier.import',
            // User
            'user.view', 'user.create', 'user.update', 'user.delete',
            // Role
            'role.view', 'role.create', 'role.update', 'role.delete',
            // Store
            'store.view', 'store.create', 'store.update', 'store.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $all = $permissions;

        // ===== Roles (prioritas admin) =====
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($all); // admin = semua

        $kasir = Role::firstOrCreate(['name' => 'kasir']);
        $kasir->syncPermissions([
            'dashboard.view',
            'produk.view', 'produk.create', 'produk.update',
            'transaksi.view', 'transaksi.create',
            'anggota.view',
        ]);

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->syncPermissions($all); // akses penuh global via Gate::before

        // ===== Role baru (akses semua toko) =====
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'dashboard.view',
            'produk.view', 'produk.create', 'produk.update', 'produk.delete',
            'transaksi.view', 'transaksi.create', 'transaksi.update', 'transaksi.delete',
            'laporan.view',
            'anggota.view', 'anggota.create', 'anggota.update',
            'supplier.view', 'supplier.create', 'supplier.update', 'supplier.import',
            'store.view',
        ]);

        $akuntansi = Role::firstOrCreate(['name' => 'akuntansi']);
        $akuntansi->syncPermissions([
            'dashboard.view',
            'laporan.view',
            'anggota.view', 'anggota.export',
        ]);

        // ===== Role per-toko =====
        $kepalaToko = Role::firstOrCreate(['name' => 'kepala-toko']);
        $kepalaToko->syncPermissions([
            'dashboard.view',
            'produk.view', 'produk.create', 'produk.update', 'produk.delete',
            'transaksi.view', 'transaksi.create', 'transaksi.update',
            'anggota.view', 'anggota.create', 'anggota.update',
            'supplier.view', 'supplier.create', 'supplier.update', 'supplier.import',
        ]);

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
