<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\StoreSwitchController;
use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\StoreController;
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
        ->middleware('permission:transaksi.manage')
        ->name('pos.index');

    Route::get('/produk', fn () => Inertia::render('Produk/Index'))
        ->middleware('permission:produk.manage')
        ->name('produk.index');

    Route::get('/laporan', fn () => Inertia::render('Laporan/Index'))
        ->middleware('permission:laporan.view')
        ->name('laporan.index');

    Route::get('/pengguna', fn () => Inertia::render('Pengguna/Index'))
        ->middleware('permission:user.manage')
        ->name('pengguna.index');

    Route::get('anggota/export', [AnggotaController::class, 'export'])
        ->middleware('permission:anggota.manage')
        ->name('anggota.export');

    Route::post('anggota/import', [AnggotaController::class, 'import'])
        ->middleware('permission:anggota.manage')
        ->name('anggota.import');

    Route::resource('anggota', AnggotaController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('permission:anggota.manage')
        ->parameters(['anggota' => 'anggota']);

    // ===== Toko =====
    Route::resource('toko', StoreController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('permission:store.manage')
        ->parameters(['toko' => 'store']);
});