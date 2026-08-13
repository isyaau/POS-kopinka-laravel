<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStokOpnameRequest;
use App\Http\Requests\UpdateStokOpnameRequest;
use App\Models\Produk;
use App\Models\Store;
use App\Models\StokOpname;
use App\Models\StokOpnameDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StokOpnameController extends Controller
{
    /**
     * Daftar opname, di-scope sesuai toko aktif.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = StokOpname::query()
            ->with(['store', 'details'])
            ->orderByDesc('tanggal');

        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('no_opname', 'ilike', "%{$search}%")
                    ->orWhere('petugas', 'ilike', "%{$search}%");
            });
        }

        $opname = $query->paginate($limit)->withQueryString();

        // Produk untuk dropdown: butuh stok sistem per-toko
        $sid = ($storeId && $storeId !== 'all') ? (int) $storeId : null;

        return Inertia::render('StokOpname/Index', [
            'opname' => $opname,
            'produk' => Produk::with('stoks')
                ->orderBy('nama_barang')
                ->get(['id', 'kode_barang', 'nama_barang', 'no_rak', 'harga_jual'])
                ->map(function ($p) use ($sid) {
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
     * Simpan opname + detail. Jika status selesai → reconcile stok riil.
     */
    public function store(StoreStokOpnameRequest $request): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        $store = Store::find(($storeId && $storeId !== 'all') ? $storeId : null);
        $tanggal = $validated['tanggal'] ?? now()->toDateString();

        try {
            $opname = DB::transaction(function () use ($validated, $store, $tanggal) {
                $items = $validated['items'] ?? [];

                $noOpname = (new StokOpname())->generateNoOpname($store?->kode, $tanggal);

                $opname = StokOpname::create([
                    'no_opname' => $noOpname,
                    'tanggal' => $tanggal,
                    'status' => $validated['status'] ?? 'draft',
                    'petugas' => $validated['petugas'] ?? null,
                    'keterangan' => $validated['keterangan'] ?? null,
                    'store_id' => $store?->id,
                ]);

                $storeIdFinal = $store?->id;

                foreach ($items as $item) {
                    $produk = Produk::find($item['produk_id']);
                    $stokSistem = $produk ? $produk->stokDi($storeIdFinal) : (int) ($item['stok_sistem'] ?? 0);
                    $stokFisik = (int) ($item['stok_fisik'] ?? 0);
                    $selisih = $stokFisik - $stokSistem;

                    StokOpnameDetail::create([
                        'stok_opname_id' => $opname->id,
                        'produk_id' => $item['produk_id'],
                        'nama_barang' => $item['nama_barang'] ?? $produk?->nama_barang,
                        'stok_sistem' => $stokSistem,
                        'stok_fisik' => $stokFisik,
                        'selisih' => $selisih,
                    ]);

                    // Reconcile jika status selesai
                    if ($opname->status === 'selesai' && $produk && $selisih !== 0) {
                        if ($selisih > 0) {
                            $produk->tambahStok($selisih, "Opname {$noOpname}", $storeIdFinal);
                        } else {
                            $produk->kurangiStok(abs($selisih), "Opname {$noOpname}", $storeIdFinal);
                        }
                    }
                }

                return $opname;
            });

            return back()->with('success', "Stok opname {$opname->no_opname} berhasil disimpan.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyimpan stok opname: ' . $e->getMessage());
        }
    }

    /**
     * Update: rollback reconcile lama (jika sebelumnya selesai), simpan baru,
     * lalu reconcile ulang (jika status selesai).
     */
    public function update(UpdateStokOpnameRequest $request, StokOpname $stokOpname): RedirectResponse
    {
        $storeId = session('store_id');
        $storeIdFinal = ($storeId && $storeId !== 'all') ? $storeId : null;
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($stokOpname, $validated, $storeIdFinal) {
                // Rollback reconcile lama jika sebelumnya selesai
                if ($stokOpname->status === 'selesai') {
                    foreach ($stokOpname->details as $detail) {
                        if ($detail->produk_id && $detail->selisih !== 0) {
                            $produk = Produk::find($detail->produk_id);
                            if ($produk) {
                                if ($detail->selisih > 0) {
                                    $produk->kurangiStok($detail->selisih, "Rollback opname {$stokOpname->no_opname}", $storeIdFinal);
                                } else {
                                    $produk->tambahStok(abs($detail->selisih), "Rollback opname {$stokOpname->no_opname}", $storeIdFinal);
                                }
                            }
                        }
                    }
                }

                $stokOpname->details()->delete();

                $items = $validated['items'] ?? [];

                $stokOpname->update([
                    'tanggal' => $validated['tanggal'] ?? $stokOpname->tanggal,
                    'status' => $validated['status'] ?? $stokOpname->status,
                    'petugas' => $validated['petugas'] ?? null,
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);

                foreach ($items as $item) {
                    $produk = Produk::find($item['produk_id']);
                    $stokSistem = $produk ? $produk->stokDi($storeIdFinal) : (int) ($item['stok_sistem'] ?? 0);
                    $stokFisik = (int) ($item['stok_fisik'] ?? 0);
                    $selisih = $stokFisik - $stokSistem;

                    StokOpnameDetail::create([
                        'stok_opname_id' => $stokOpname->id,
                        'produk_id' => $item['produk_id'],
                        'nama_barang' => $item['nama_barang'] ?? $produk?->nama_barang,
                        'stok_sistem' => $stokSistem,
                        'stok_fisik' => $stokFisik,
                        'selisih' => $selisih,
                    ]);

                    // Reconcile ulang jika status selesai
                    if ($stokOpname->status === 'selesai' && $produk && $selisih !== 0) {
                        if ($selisih > 0) {
                            $produk->tambahStok($selisih, "Opname {$stokOpname->no_opname}", $storeIdFinal);
                        } else {
                            $produk->kurangiStok(abs($selisih), "Opname {$stokOpname->no_opname}", $storeIdFinal);
                        }
                    }
                }
            });

            return back()->with('success', "Stok opname {$stokOpname->no_opname} berhasil diperbarui.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memperbarui stok opname: ' . $e->getMessage());
        }
    }

    /**
     * Hapus opname + rollback reconcile (jika status selesai).
     */
    public function destroy(StokOpname $stokOpname): RedirectResponse
    {
        $storeId = session('store_id');
        $storeIdFinal = ($storeId && $storeId !== 'all') ? $storeId : null;

        try {
            DB::transaction(function () use ($stokOpname, $storeIdFinal) {
                if ($stokOpname->status === 'selesai') {
                    foreach ($stokOpname->details as $detail) {
                        if ($detail->produk_id && $detail->selisih !== 0) {
                            $produk = Produk::find($detail->produk_id);
                            if ($produk) {
                                if ($detail->selisih > 0) {
                                    $produk->kurangiStok($detail->selisih, "Hapus opname {$stokOpname->no_opname}", $storeIdFinal);
                                } else {
                                    $produk->tambahStok(abs($detail->selisih), "Hapus opname {$stokOpname->no_opname}", $storeIdFinal);
                                }
                            }
                        }
                    }
                }

                $stokOpname->delete();
            });

            return back()->with('success', "Stok opname {$stokOpname->no_opname} berhasil dihapus.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus stok opname: ' . $e->getMessage());
        }
    }
}
