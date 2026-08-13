<?php

namespace App\Http\Controllers;

use App\Exports\TransaksiExport;
use App\Http\Requests\ImportTransaksiRequest;
use App\Http\Requests\StoreTransaksiRequest;
use App\Http\Requests\UpdateTransaksiRequest;
use App\Imports\TransaksiImport;
use App\Models\Anggota;
use App\Models\Produk;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\Voucher;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransaksiController extends Controller
{
    /**
     * Menampilkan daftar transaksi, di-scope sesuai toko aktif.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = Transaksi::query()
            ->with(['store', 'details'])
            ->orderByDesc('tanggal');

        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('no_nota', 'ilike', "%{$search}%")
                    ->orWhere('nama_anggota', 'ilike', "%{$search}%")
                    ->orWhere('no_kasir', 'ilike', "%{$search}%");
            });
        }

        $transaksi = $query->paginate($limit)->withQueryString();

        return Inertia::render('Transaksi/Index', [
            'transaksi' => $transaksi,
            'anggota' => Anggota::orderBy('nama')->get(['id', 'nip', 'nama']),
            'produk' => Produk::with('stoks')
                ->orderBy('nama_barang')
                ->get(['id', 'kode_barang', 'nama_barang', 'harga_jual'])
                ->map(function ($p) use ($storeId) {
                    $sid = ($storeId && $storeId !== 'all') ? (int) $storeId : null;
                    $p->stok = $p->stokDi($sid);

                    return $p;
                }),
            'filters' => [
                'search' => $request->input('search', ''),
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Simpan transaksi baru + detail item + kurangi stok.
     */
    public function store(StoreTransaksiRequest $request): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        $store = Store::find(($storeId && $storeId !== 'all') ? $storeId : null);

        $noNotaManual = trim((string) ($validated['no_nota'] ?? ''));
        $autoNota = $noNotaManual === '';

        try {
            $transaksi = null;

            // Retry hingga 3x: bila no_nota auto bentrok (2 kasir submit
            // bersamaan), generate ulang nomor dan coba simpan lagi.
            for ($attempt = 1; $attempt <= 3 && $transaksi === null; $attempt++) {
                try {
                    $transaksi = DB::transaction(function () use ($validated, $store, $autoNota, $noNotaManual) {
                        $items = $validated['items'] ?? [];

                        // Hitung nilai dari subtotal item
                        $nilai = 0;
                        $detailData = [];

                        foreach ($items as $item) {
                            $harga = (float) ($item['harga'] ?? 0);
                            $diskonItem = (float) ($item['diskon_item'] ?? 0);
                            $qty = (int) ($item['qty'] ?? 1);
                            $subtotal = max(0, $qty * ($harga - $diskonItem));
                            $nilai += $subtotal;

                            $produk = isset($item['produk_id']) ? Produk::find($item['produk_id']) : null;

                            $detailData[] = [
                                'produk_id' => $item['produk_id'] ?? null,
                                'nama_barang' => $item['nama_barang'] ?? $produk?->nama_barang,
                                'qty' => $qty,
                                'harga' => $harga,
                                'diskon_item' => $diskonItem,
                                'subtotal' => $subtotal,
                            ];
                        }

                        $diskon = (float) ($validated['diskon'] ?? 0);
                        $jual = max(0, $nilai - $diskon);

                        // Kumpulkan voucher yang dipakai (bisa beberapa per transaksi)
                        $voucherIds = $validated['voucher_ids'] ?? [];
                        $voucherTotal = 0;
                        $usedVouchers = [];

                        if (! empty($voucherIds)) {
                            $usedVouchers = Voucher::query()
                                ->whereIn('id', $voucherIds)
                                ->where('status', 'aktif')
                                ->get();
                            $voucherTotal = $usedVouchers->sum('nominal');
                        }

                        $transaksi = Transaksi::create([
                            'no_nota' => $autoNota ? (new Transaksi())->generateNoNota($store) : $noNotaManual,
                            'no_kasir' => $validated['no_kasir'] ?? null,
                            'anggota_id' => $validated['anggota_id'] ?? null,
                            'nama_anggota' => $validated['nama_anggota'] ?? ($validated['anggota_id'] ? null : 'Umum'),
                            'nilai' => $nilai,
                            'diskon' => $diskon,
                            'jual' => $jual,
                            'usaha' => $validated['usaha'] ?? 0,
                            'jasa' => $validated['jasa'] ?? 0,
                            'ppn' => $validated['ppn'] ?? 0,
                            'cash' => $validated['cash'] ?? 0,
                            'qris' => $validated['qris'] ?? 0,
                            'edc' => $validated['edc'] ?? 0,
                            'voucher' => $validated['voucher'] ?? $voucherTotal,
                            'piutang' => $validated['piutang'] ?? 0,
                            'tanggal' => $validated['tanggal'] ?? now()->toDateString(),
                            'store_id' => $store?->id,
                        ]);

                        // Tandai voucher terpakai
                        foreach ($usedVouchers as $v) {
                            $v->update(['status' => 'terpakai']);
                        }

                        foreach ($detailData as $detail) {
                            TransaksiDetail::create(array_merge($detail, ['transaksi_id' => $transaksi->id]));

                            // Kurangi stok produk
                            if ($detail['produk_id']) {
                                $produk = Produk::find($detail['produk_id']);
                                if ($produk && $produk->stokDi($store?->id) >= $detail['qty']) {
                                    $produk->kurangiStok($detail['qty'], "Transaksi {$transaksi->no_nota}", $store?->id);
                                }
                            }
                        }

                        return $transaksi;
                    });
                } catch (UniqueConstraintViolationException $e) {
                    // Hanya bisa retry bila no_nota di-generate otomatis.
                    // Bila manual, duplikat = error validasi yang sah.
                    if (! $autoNota) {
                        throw $e;
                    }

                    $transaksi = null;
                }
            }

            if ($transaksi === null) {
                throw new \RuntimeException('Gagal membuat nomor nota unik. Silakan coba lagi.');
            }

            return back()->with('success', "Transaksi {$transaksi->no_nota} berhasil disimpan.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyimpan transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Perbarui transaksi: kembalikan stok lama, simpan baru, kurangi stok baru.
     */
    public function update(UpdateTransaksiRequest $request, Transaksi $transaksi): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        $storeIdFinal = ($storeId && $storeId !== 'all') ? $storeId : null;

        try {
            DB::transaction(function () use ($transaksi, $validated, $storeIdFinal) {
                // Kembalikan stok dari detail lama
                foreach ($transaksi->details as $detail) {
                    if ($detail->produk_id) {
                        $produk = Produk::find($detail->produk_id);
                        if ($produk) {
                            $produk->tambahStok($detail->qty, "Reversi transaksi {$transaksi->no_nota}", $storeIdFinal);
                        }
                    }
                }

                // Hapus detail lama
                $transaksi->details()->delete();

                $items = $validated['items'] ?? [];

                $nilai = 0;
                $detailData = [];

                foreach ($items as $item) {
                    $harga = (float) ($item['harga'] ?? 0);
                    $diskonItem = (float) ($item['diskon_item'] ?? 0);
                    $qty = (int) ($item['qty'] ?? 1);
                    $subtotal = max(0, $qty * ($harga - $diskonItem));
                    $nilai += $subtotal;

                    $produk = isset($item['produk_id']) ? Produk::find($item['produk_id']) : null;

                    $detailData[] = [
                        'produk_id' => $item['produk_id'] ?? null,
                        'nama_barang' => $item['nama_barang'] ?? $produk?->nama_barang,
                        'qty' => $qty,
                        'harga' => $harga,
                        'diskon_item' => $diskonItem,
                        'subtotal' => $subtotal,
                    ];
                }

                $diskon = (float) ($validated['diskon'] ?? 0);
                $jual = max(0, $nilai - $diskon);

                $transaksi->update([
                    'no_kasir' => $validated['no_kasir'] ?? null,
                    'anggota_id' => $validated['anggota_id'] ?? null,
                    'nama_anggota' => $validated['nama_anggota'] ?? ($validated['anggota_id'] ? null : 'Umum'),
                    'nilai' => $nilai,
                    'diskon' => $diskon,
                    'jual' => $jual,
                    'usaha' => $validated['usaha'] ?? 0,
                    'jasa' => $validated['jasa'] ?? 0,
                    'ppn' => $validated['ppn'] ?? 0,
                    'cash' => $validated['cash'] ?? 0,
                    'qris' => $validated['qris'] ?? 0,
                    'edc' => $validated['edc'] ?? 0,
                    'voucher' => $validated['voucher'] ?? 0,
                    'piutang' => $validated['piutang'] ?? 0,
                    'tanggal' => $validated['tanggal'] ?? now()->toDateString(),
                ]);

                foreach ($detailData as $detail) {
                    TransaksiDetail::create(array_merge($detail, ['transaksi_id' => $transaksi->id]));

                    if ($detail['produk_id']) {
                        $produk = Produk::find($detail['produk_id']);
                        if ($produk && $produk->stokDi($storeIdFinal) >= $detail['qty']) {
                            $produk->kurangiStok($detail['qty'], "Transaksi {$transaksi->no_nota}", $storeIdFinal);
                        }
                    }
                }
            });

            return back()->with('success', "Transaksi {$transaksi->no_nota} berhasil diperbarui.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memperbarui transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Hapus transaksi + kembalikan stok.
     */
    public function destroy(Transaksi $transaksi): RedirectResponse
    {
        $storeId = session('store_id');
        $storeIdFinal = ($storeId && $storeId !== 'all') ? $storeId : null;

        try {
            DB::transaction(function () use ($transaksi, $storeIdFinal) {
                foreach ($transaksi->details as $detail) {
                    if ($detail->produk_id) {
                        $produk = Produk::find($detail->produk_id);
                        if ($produk) {
                            $produk->tambahStok($detail->qty, "Hapus transaksi {$transaksi->no_nota}", $storeIdFinal);
                        }
                    }
                }

                $transaksi->delete();
            });

            return back()->with('success', "Transaksi {$transaksi->no_nota} berhasil dihapus.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Export data transaksi ke Excel.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $storeId = session('store_id');

        $filters = [
            'store_id' => $storeId,
            'search' => $request->input('search', ''),
            'template' => $request->boolean('template'),
        ];

        $namaFile = $request->boolean('template')
            ? 'template-transaksi.xlsx'
            : 'data-transaksi-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new TransaksiExport($filters), $namaFile);
    }

    /**
     * Import data transaksi dari file Excel/CSV.
     */
    public function import(ImportTransaksiRequest $request): RedirectResponse
    {
        $storeId = session('store_id');

        $import = new TransaksiImport(($storeId && $storeId !== 'all') ? $storeId : null);
        Excel::import($import, $request->file('file'));

        $message = "Import selesai: {$import->imported} berhasil, {$import->skipped} dilewati.";

        if (! empty($import->errors)) {
            $detail = implode('; ', array_slice($import->errors, 0, 5));
            $message .= ' ' . $detail;
        }

        return back()->with('success', $message);
    }
}
