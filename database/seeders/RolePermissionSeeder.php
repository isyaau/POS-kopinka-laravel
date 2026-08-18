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
            'transaksi.export', 'transaksi.import',
            // Stok
            'stok.view',
            // Voucher (kupon + barcode)
            'voucher.view', 'voucher.create', 'voucher.update', 'voucher.delete',
            'voucher.export', 'voucher.import',
            // Laporan
            'laporan.view',
            // Anggota
            'anggota.view', 'anggota.create', 'anggota.update', 'anggota.delete',
            'anggota.export', 'anggota.import',
            // Supplier
            'supplier.view', 'supplier.create', 'supplier.update', 'supplier.delete',
            'supplier.export', 'supplier.import',
            // Pembelian
            'pembelian.view', 'pembelian.create', 'pembelian.update', 'pembelian.delete',
            // Terima Barang (konsinyasi / retur toko)
            'terima-barang.view', 'terima-barang.create', 'terima-barang.update', 'terima-barang.delete',
            // Stok Opname
            'stok-opname.view', 'stok-opname.create', 'stok-opname.update', 'stok-opname.delete',
            // Retur / Tukar Pembelian
            'retur-pembelian.view', 'retur-pembelian.create', 'retur-pembelian.update', 'retur-pembelian.delete',
            // Kirim Barang (mutasi antar toko)
            'kirim-barang.view', 'kirim-barang.create', 'kirim-barang.update', 'kirim-barang.delete',
            // Biaya Operasional
            'biaya-operasional.view', 'biaya-operasional.create', 'biaya-operasional.update', 'biaya-operasional.delete',
            // Pembayaran Hutang Supplier
            'pembayaran-hutang.view', 'pembayaran-hutang.create', 'pembayaran-hutang.update', 'pembayaran-hutang.delete',
            // Retur & Pembayaran Barang Konsinyi
            'konsinyi.view', 'konsinyi.create', 'konsinyi.update', 'konsinyi.delete',
            // Penerimaan Angsuran Piutang Dagang
            'penerimaan-angsuran.view', 'penerimaan-angsuran.create', 'penerimaan-angsuran.update', 'penerimaan-angsuran.delete',
            // Register Tagihan Piutang Dagang (Potong Gaji)
            'register-tagihan-piutang.view', 'register-tagihan-piutang.create', 'register-tagihan-piutang.update', 'register-tagihan-piutang.delete',
            // Penerimaan Angsuran Piutang Dagang Potong Gaji
            'penerimaan-angsuran-potong-gaji.view', 'penerimaan-angsuran-potong-gaji.create', 'penerimaan-angsuran-potong-gaji.update', 'penerimaan-angsuran-potong-gaji.delete',
            // Gagal Debet Tagihan Piutang
            'gagal-debet-piutang.view', 'gagal-debet-piutang.create', 'gagal-debet-piutang.update', 'gagal-debet-piutang.delete',
            // Pengembalian Lebih Bayar Potong Gaji
            'pengembalian-lebih-bayar-potong-gaji.view', 'pengembalian-lebih-bayar-potong-gaji.create', 'pengembalian-lebih-bayar-potong-gaji.update', 'pengembalian-lebih-bayar-potong-gaji.delete',
            // Register Label Etalase Barang
            'register-label-etalase-barang.view', 'register-label-etalase-barang.create', 'register-label-etalase-barang.update', 'register-label-etalase-barang.delete',
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
            'stok.view',
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
            'stok.view',
            'laporan.view',
            'anggota.view', 'anggota.create', 'anggota.update',
            'supplier.view', 'supplier.create', 'supplier.update', 'supplier.import',
            'pembelian.view', 'pembelian.create', 'pembelian.update',
            'terima-barang.view', 'terima-barang.create', 'terima-barang.update', 'terima-barang.delete',
            'stok-opname.view', 'stok-opname.create', 'stok-opname.update', 'stok-opname.delete',
            'kirim-barang.view', 'kirim-barang.create', 'kirim-barang.update',
            'retur-pembelian.view', 'retur-pembelian.create', 'retur-pembelian.update',
            'biaya-operasional.view', 'biaya-operasional.create', 'biaya-operasional.update', 'biaya-operasional.delete',
            'pembayaran-hutang.view', 'pembayaran-hutang.create', 'pembayaran-hutang.update', 'pembayaran-hutang.delete',
            'konsinyi.view', 'konsinyi.create', 'konsinyi.update', 'konsinyi.delete',
            'penerimaan-angsuran.view', 'penerimaan-angsuran.create', 'penerimaan-angsuran.update', 'penerimaan-angsuran.delete',
            'register-tagihan-piutang.view', 'register-tagihan-piutang.create', 'register-tagihan-piutang.update', 'register-tagihan-piutang.delete',
            'penerimaan-angsuran-potong-gaji.view', 'penerimaan-angsuran-potong-gaji.create', 'penerimaan-angsuran-potong-gaji.update', 'penerimaan-angsuran-potong-gaji.delete',
            'gagal-debet-piutang.view', 'gagal-debet-piutang.create', 'gagal-debet-piutang.update', 'gagal-debet-piutang.delete',
            'pengembalian-lebih-bayar-potong-gaji.view', 'pengembalian-lebih-bayar-potong-gaji.create', 'pengembalian-lebih-bayar-potong-gaji.update', 'pengembalian-lebih-bayar-potong-gaji.delete',
            'register-label-etalase-barang.view', 'register-label-etalase-barang.create', 'register-label-etalase-barang.update', 'register-label-etalase-barang.delete',
            'store.view',
        ]);

        $akuntansi = Role::firstOrCreate(['name' => 'akuntansi']);
        $akuntansi->syncPermissions([
            'dashboard.view',
            'laporan.view',
            'anggota.view', 'anggota.export',
            'biaya-operasional.view', 'biaya-operasional.create', 'biaya-operasional.update', 'biaya-operasional.delete',
            'pembayaran-hutang.view', 'pembayaran-hutang.create', 'pembayaran-hutang.update', 'pembayaran-hutang.delete',
            'penerimaan-angsuran.view', 'penerimaan-angsuran.create', 'penerimaan-angsuran.update', 'penerimaan-angsuran.delete',
            'register-tagihan-piutang.view', 'register-tagihan-piutang.create', 'register-tagihan-piutang.update', 'register-tagihan-piutang.delete',
            'gagal-debet-piutang.view', 'gagal-debet-piutang.create', 'gagal-debet-piutang.update', 'gagal-debet-piutang.delete',
            'pengembalian-lebih-bayar-potong-gaji.view', 'pengembalian-lebih-bayar-potong-gaji.create', 'pengembalian-lebih-bayar-potong-gaji.update', 'pengembalian-lebih-bayar-potong-gaji.delete',
            'register-label-etalase-barang.view', 'register-label-etalase-barang.create', 'register-label-etalase-barang.update', 'register-label-etalase-barang.delete',
        ]);

        // ===== Role per-toko =====
        $kepalaToko = Role::firstOrCreate(['name' => 'kepala-toko']);
        $kepalaToko->syncPermissions([
            'dashboard.view',
            'produk.view', 'produk.create', 'produk.update', 'produk.delete',
            'transaksi.view', 'transaksi.create', 'transaksi.update',
            'stok.view',
            'anggota.view', 'anggota.create', 'anggota.update',
            'supplier.view', 'supplier.create', 'supplier.update', 'supplier.import',
            'pembelian.view', 'pembelian.create', 'pembelian.update',
            'terima-barang.view', 'terima-barang.create', 'terima-barang.update', 'terima-barang.delete',
            'stok-opname.view', 'stok-opname.create', 'stok-opname.update', 'stok-opname.delete',
            'kirim-barang.view', 'kirim-barang.create', 'kirim-barang.update',
            'retur-pembelian.view', 'retur-pembelian.create', 'retur-pembelian.update',
            'biaya-operasional.view', 'biaya-operasional.create', 'biaya-operasional.update', 'biaya-operasional.delete',
            'pembayaran-hutang.view', 'pembayaran-hutang.create', 'pembayaran-hutang.update', 'pembayaran-hutang.delete',
            'konsinyi.view', 'konsinyi.create', 'konsinyi.update', 'konsinyi.delete',
            'penerimaan-angsuran.view', 'penerimaan-angsuran.create', 'penerimaan-angsuran.update', 'penerimaan-angsuran.delete',
            'register-tagihan-piutang.view', 'register-tagihan-piutang.create', 'register-tagihan-piutang.update', 'register-tagihan-piutang.delete',
            'gagal-debet-piutang.view', 'gagal-debet-piutang.create', 'gagal-debet-piutang.update', 'gagal-debet-piutang.delete',
            'pengembalian-lebih-bayar-potong-gaji.view', 'pengembalian-lebih-bayar-potong-gaji.create', 'pengembalian-lebih-bayar-potong-gaji.update', 'pengembalian-lebih-bayar-potong-gaji.delete',
            'register-label-etalase-barang.view', 'register-label-etalase-barang.create', 'register-label-etalase-barang.update', 'register-label-etalase-barang.delete',
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
