<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePembayaranHutangRequest;
use App\Http\Requests\UpdatePembayaranHutangRequest;
use App\Models\PembayaranHutang;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PembayaranHutangController extends Controller
{
    /**
     * Daftar pembayaran hutang supplier, di-scope sesuai toko aktif.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = PembayaranHutang::query()
            ->with(['supplier', 'user', 'store'])
            ->orderByDesc('tanggal_bayar')
            ->orderByDesc('id');

        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('no_transaksi', 'ilike', "%{$search}%")
                    ->orWhere('no_faktur', 'ilike', "%{$search}%")
                    ->orWhere('no_bukti', 'ilike', "%{$search}%")
                    ->orWhere('nama_supplier', 'ilike', "%{$search}%")
                    ->orWhere('kode_supplier', 'ilike', "%{$search}%");
            });
        }

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        if ($tanggalMulai && $tanggalSelesai) {
            $query->whereBetween('tanggal_bayar', [$tanggalMulai, $tanggalSelesai]);
        } elseif ($tanggalMulai) {
            $query->whereDate('tanggal_bayar', '>=', $tanggalMulai);
        } elseif ($tanggalSelesai) {
            $query->whereDate('tanggal_bayar', '<=', $tanggalSelesai);
        }

        $pembayaran = $query->paginate($limit)->withQueryString();

        return Inertia::render('PembayaranHutang/Index', [
            'pembayaran' => $pembayaran,
            'suppliers' => Supplier::orderBy('nama')->get(['id', 'kode', 'nama']),
            'filters' => [
                'search' => $request->input('search', ''),
                'supplier_id' => $supplierId ?? '',
                'tanggal_mulai' => $tanggalMulai ?? '',
                'tanggal_selesai' => $tanggalSelesai ?? '',
                'limit' => $limit,
            ],
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
        ]);
    }

    /**
     * Simpan pembayaran hutang baru.
     */
    public function store(StorePembayaranHutangRequest $request): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        $data = array_merge($validated, [
            'store_id' => ($storeId && $storeId !== 'all') ? $storeId : null,
            'user_id' => auth()->id(),
        ]);

        PembayaranHutang::create($data);

        return back()->with('success', 'Pembayaran hutang supplier berhasil dicatat.');
    }

    /**
     * Perbarui pembayaran hutang.
     */
    public function update(UpdatePembayaranHutangRequest $request, PembayaranHutang $pembayaranHutang): RedirectResponse
    {
        $pembayaranHutang->update($request->validated());

        return back()->with('success', 'Data pembayaran hutang berhasil diperbarui.');
    }

    /**
     * Hapus pembayaran hutang.
     */
    public function destroy(PembayaranHutang $pembayaranHutang): RedirectResponse
    {
        $pembayaranHutang->delete();

        return back()->with('success', 'Data pembayaran hutang berhasil dihapus.');
    }
}
