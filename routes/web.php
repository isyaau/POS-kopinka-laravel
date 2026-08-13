<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\StoreSwitchController;
use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\ReturPembelianController;
use App\Http\Controllers\KirimBarangController;
use App\Http\Controllers\MutasiController;
use App\Http\Controllers\TerimaBarangController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'message' => 'Halo Kasir, Server Toko Terhubung!',
    ]);
});

// ===== Auth =====
Route::get('/login', [AuthController::class, 'showLogin'])
    ->middleware('guest')
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('guest');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ===== Area yang wajib login =====
Route::middleware(['auth'])->group(function () {
    // Ganti toko aktif
    Route::post('/store/switch', [StoreSwitchController::class, 'switch'])
        ->name('store.switch');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    // ===== Kasir / POS =====
    Route::get('/pos', [PosController::class, 'index'])
        ->middleware('permission:transaksi.view')
        ->name('pos.index');

    // ===== Transaksi (CRUD + detail item + stok otomatis) =====
    Route::get('transaksi/export', [TransaksiController::class, 'export'])
        ->middleware('permission:transaksi.export')
        ->name('transaksi.export');

    Route::post('transaksi/import', [TransaksiController::class, 'import'])
        ->middleware('permission:transaksi.import')
        ->name('transaksi.import');

    Route::get('transaksi', [TransaksiController::class, 'index'])
        ->middleware('permission:transaksi.view')
        ->name('transaksi.index');

    Route::post('transaksi', [TransaksiController::class, 'store'])
        ->middleware('permission:transaksi.create')
        ->name('transaksi.store');

    Route::put('transaksi/{transaksi}', [TransaksiController::class, 'update'])
        ->middleware('permission:transaksi.update')
        ->name('transaksi.update');

    Route::delete('transaksi/{transaksi}', [TransaksiController::class, 'destroy'])
        ->middleware('permission:transaksi.delete')
        ->name('transaksi.destroy');

    // ===== Stok =====
    Route::get('stok/bulanan', [StokController::class, 'bulanan'])
        ->middleware('permission:stok.view')
        ->name('stok.bulanan');

    Route::get('stok', [StokController::class, 'index'])
        ->middleware('permission:stok.view')
        ->name('stok.index');

    // ===== Voucher (kupon + barcode) =====
    Route::get('voucher/export', [VoucherController::class, 'export'])
        ->middleware('permission:voucher.export')
        ->name('voucher.export');

    Route::post('voucher/import', [VoucherController::class, 'import'])
        ->middleware('permission:voucher.import')
        ->name('voucher.import');

    Route::get('voucher', [VoucherController::class, 'index'])
        ->middleware('permission:voucher.view')
        ->name('voucher.index');

    Route::post('voucher', [VoucherController::class, 'store'])
        ->middleware('permission:voucher.create')
        ->name('voucher.store');

    Route::put('voucher/{voucher}', [VoucherController::class, 'update'])
        ->middleware('permission:voucher.update')
        ->name('voucher.update');

    Route::delete('voucher/{voucher}', [VoucherController::class, 'destroy'])
        ->middleware('permission:voucher.delete')
        ->name('voucher.destroy');

    // ===== Produk / Persediaan (CRUD) =====
    Route::get('produk/export', [ProdukController::class, 'export'])
        ->middleware('permission:produk.export')
        ->name('produk.export');

    Route::post('produk/import', [ProdukController::class, 'import'])
        ->middleware('permission:produk.import')
        ->name('produk.import');

    Route::get('produk', [ProdukController::class, 'index'])
        ->middleware('permission:produk.view')
        ->name('produk.index');

    Route::post('produk', [ProdukController::class, 'store'])
        ->middleware('permission:produk.create')
        ->name('produk.store');

    Route::put('produk/{produk}', [ProdukController::class, 'update'])
        ->middleware('permission:produk.update')
        ->name('produk.update');

    Route::delete('produk/{produk}', [ProdukController::class, 'destroy'])
        ->middleware('permission:produk.delete')
        ->name('produk.destroy');

    Route::post('produk/{produk}/restore', [ProdukController::class, 'restore'])
        ->middleware('permission:produk.delete')
        ->name('produk.restore');

    // ===== Retur / Tukar Pembelian =====
    Route::get('retur-pembelian', [ReturPembelianController::class, 'index'])
        ->middleware('permission:retur-pembelian.view')
        ->name('retur-pembelian.index');

    Route::post('retur-pembelian', [ReturPembelianController::class, 'store'])
        ->middleware('permission:retur-pembelian.create')
        ->name('retur-pembelian.store');

    Route::put('retur-pembelian/{retur_pembelian}', [ReturPembelianController::class, 'update'])
        ->middleware('permission:retur-pembelian.update')
        ->name('retur-pembelian.update');

    Route::delete('retur-pembelian/{retur_pembelian}', [ReturPembelianController::class, 'destroy'])
        ->middleware('permission:retur-pembelian.delete')
        ->name('retur-pembelian.destroy');

    Route::get('/laporan', [LaporanController::class, 'index'])
        ->middleware('permission:laporan.view')
        ->name('laporan.index');

    // ===== Anggota =====
    Route::get('anggota/export', [AnggotaController::class, 'export'])
        ->middleware('permission:anggota.export')
        ->name('anggota.export');

    Route::post('anggota/import', [AnggotaController::class, 'import'])
        ->middleware('permission:anggota.import')
        ->name('anggota.import');

    Route::get('anggota', [AnggotaController::class, 'index'])
        ->middleware('permission:anggota.view')
        ->name('anggota.index');

    Route::post('anggota', [AnggotaController::class, 'store'])
        ->middleware('permission:anggota.create')
        ->name('anggota.store');

    Route::put('anggota/{anggota}', [AnggotaController::class, 'update'])
        ->middleware('permission:anggota.update')
        ->name('anggota.update');

    Route::delete('anggota/{anggota}', [AnggotaController::class, 'destroy'])
        ->middleware('permission:anggota.delete')
        ->name('anggota.destroy');

    // ===== Toko =====
    Route::get('toko', [StoreController::class, 'index'])
        ->middleware('permission:store.view')
        ->name('toko.index');

    Route::post('toko', [StoreController::class, 'store'])
        ->middleware('permission:store.create')
        ->name('toko.store');

    Route::put('toko/{store}', [StoreController::class, 'update'])
        ->middleware('permission:store.update')
        ->name('toko.update');

    Route::delete('toko/{store}', [StoreController::class, 'destroy'])
        ->middleware('permission:store.delete')
        ->name('toko.destroy');

    // ===== Role =====
    Route::get('roles', [RoleController::class, 'index'])
        ->middleware('permission:role.view')
        ->name('roles.index');

    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('permission:role.create')
        ->name('roles.store');

    Route::put('roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:role.update')
        ->name('roles.update');

    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:role.delete')
        ->name('roles.destroy');

    // ===== Pengguna (CRUD) =====
    Route::get('pengguna', [UserController::class, 'index'])
        ->middleware('permission:user.view')
        ->name('pengguna.index');

    Route::post('pengguna', [UserController::class, 'store'])
        ->middleware('permission:user.create')
        ->name('pengguna.store');

    Route::put('pengguna/{user}', [UserController::class, 'update'])
        ->middleware('permission:user.update')
        ->name('pengguna.update');

    Route::delete('pengguna/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:user.delete')
        ->name('pengguna.destroy');

    // ===== Supplier (CRUD) =====
    Route::get('suppliers/export', [SupplierController::class, 'export'])
        ->middleware('permission:supplier.export')
        ->name('supplier.export');

    Route::post('suppliers/import', [SupplierController::class, 'import'])
        ->middleware('permission:supplier.import')
        ->name('supplier.import');

    Route::get('suppliers', [SupplierController::class, 'index'])
        ->middleware('permission:supplier.view')
        ->name('supplier.index');

    Route::post('suppliers', [SupplierController::class, 'store'])
        ->middleware('permission:supplier.create')
        ->name('supplier.store');

    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])
        ->middleware('permission:supplier.update')
        ->name('supplier.update');

    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('permission:supplier.delete')
        ->name('supplier.destroy');

    Route::post('suppliers/{supplier}/restore', [SupplierController::class, 'restore'])
        ->middleware('permission:supplier.delete')
        ->name('supplier.restore');

    // ===== Pembelian (CRUD + stok otomatis + update master produk) =====
    Route::get('pembelian', [PembelianController::class, 'index'])
        ->middleware('permission:pembelian.view')
        ->name('pembelian.index');

    Route::post('pembelian', [PembelianController::class, 'store'])
        ->middleware('permission:pembelian.create')
        ->name('pembelian.store');

    Route::put('pembelian/{pembelian}', [PembelianController::class, 'update'])
        ->middleware('permission:pembelian.update')
        ->name('pembelian.update');

    Route::delete('pembelian/{pembelian}', [PembelianController::class, 'destroy'])
        ->middleware('permission:pembelian.delete')
        ->name('pembelian.destroy');

    // ===== Terima Barang (konsinyasi / retur toko) — stok masuk, tanpa finansial =====
    Route::get('terima-barang', [TerimaBarangController::class, 'index'])
        ->middleware('permission:terima-barang.view')
        ->name('terima-barang.index');

    Route::post('terima-barang', [TerimaBarangController::class, 'store'])
        ->middleware('permission:terima-barang.create')
        ->name('terima-barang.store');

    Route::put('terima-barang/{terima_barang}', [TerimaBarangController::class, 'update'])
        ->middleware('permission:terima-barang.update')
        ->name('terima-barang.update');

    Route::delete('terima-barang/{terima_barang}', [TerimaBarangController::class, 'destroy'])
        ->middleware('permission:terima-barang.delete')
        ->name('terima-barang.destroy');

    // ===== Kirim Barang (mutasi antar toko) =====
    Route::get('kirim-barang', [KirimBarangController::class, 'index'])
        ->middleware('permission:kirim-barang.view')
        ->name('kirim-barang.index');

    Route::post('kirim-barang', [KirimBarangController::class, 'store'])
        ->middleware('permission:kirim-barang.create')
        ->name('kirim-barang.store');

    Route::put('kirim-barang/{kirim_barang}', [KirimBarangController::class, 'update'])
        ->middleware('permission:kirim-barang.update')
        ->name('kirim-barang.update');

    Route::delete('kirim-barang/{kirim_barang}', [KirimBarangController::class, 'destroy'])
        ->middleware('permission:kirim-barang.delete')
        ->name('kirim-barang.destroy');

    // ===== Cek Mutasi Produk (ledger stok) =====
    Route::get('mutasi', [MutasiController::class, 'index'])
        ->middleware('permission:stok.view')
        ->name('mutasi.index');

    // ===== Migrasi Data =====
    Route::get('migrasi', function () {
        return Inertia::render('Migrasi/Index');
    })
        ->middleware('permission:supplier.import')
        ->name('migrasi.index');
});