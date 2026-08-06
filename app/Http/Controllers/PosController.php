<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    /**
     * Layar kasir: daftar produk (di-scope toko aktif) + anggota.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $search = trim((string) $request->input('search', ''));
        $kategori = trim((string) $request->input('kategori', ''));

        $produkQuery = Produk::query()
            ->with(['supplier'])
            ->orderBy('nama_barang');

        if ($storeId && $storeId !== 'all') {
            $produkQuery->where('store_id', $storeId);
        }

        if ($search !== '') {
            $produkQuery->where(function ($q) use ($search) {
                $q->where('nama_barang', 'ilike', "%{$search}%")
                    ->orWhere('kode_barang', 'ilike', "%{$search}%");
            });
        }

        if ($kategori !== '') {
            $produkQuery->where('kategori', $kategori);
        }

        // Ambil 100 produk teratas untuk grid kasir
        $produk = $produkQuery->limit(100)->get();

        // Daftar kategori unik untuk filter
        $kategoriList = Produk::query()
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '');

        if ($storeId && $storeId !== 'all') {
            $kategoriList->where('store_id', $storeId);
        }

        $kategoriList = $kategoriList->distinct()->orderBy('kategori')->pluck('kategori');

        $anggota = Anggota::query()
            ->orderBy('nama')
            ->limit(500)
            ->get(['id', 'nip', 'nama', 'limit_transaksi']);

        // Semua voucher (aktif & belum terpakai) untuk pembayaran kasir.
        // Tanpa limit agar kode voucher apa pun yang terlihat di menu bisa dimasukkan.
        $vouchers = Voucher::query()
            ->where('status', 'aktif')
            ->where(function ($q) {
                $q->whereNull('tanggal_expired')
                    ->orWhere('tanggal_expired', '>=', now()->toDateString());
            })
            ->orderBy('kode')
            ->get(['id', 'kode', 'nama', 'nominal', 'barcode', 'status']);

        return Inertia::render('POS/Index', [
            'produk' => $produk,
            'kategori_list' => $kategoriList,
            'anggota' => $anggota,
            'vouchers' => $vouchers,
            'no_nota' => (new Transaksi())->generateNoNota(),
            'filters' => [
                'search' => $search,
                'kategori' => $kategori,
            ],
        ]);
    }
}
