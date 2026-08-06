<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\StoreSwitchController;
use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
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

    // ===== Modul (dibangun bertahap) =====
    Route::get('/pos', fn () => Inertia::render('POS/Index'))
        ->middleware('permission:transaksi.view')
        ->name('pos.index');

    Route::get('/produk', fn () => Inertia::render('Produk/Index'))
        ->middleware('permission:produk.view')
        ->name('produk.index');

    Route::get('/laporan', fn () => Inertia::render('Laporan/Index'))
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

    // ===== Migrasi Data =====
    Route::get('migrasi', function () {
        return Inertia::render('Migrasi/Index');
    })
        ->middleware('permission:supplier.import')
        ->name('migrasi.index');
});