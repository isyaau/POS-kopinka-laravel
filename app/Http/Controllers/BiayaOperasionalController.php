<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBiayaOperasionalRequest;
use App\Http\Requests\UpdateBiayaOperasionalRequest;
use App\Models\BiayaOperasional;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BiayaOperasionalController extends Controller
{
    /**
     * Daftar biaya operasional, di-scope sesuai toko aktif.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = BiayaOperasional::query()
            ->with('store')
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('no_bukti', 'ilike', "%{$search}%")
                    ->orWhere('dari_unit', 'ilike', "%{$search}%")
                    ->orWhere('kategori', 'ilike', "%{$search}%")
                    ->orWhere('keterangan', 'ilike', "%{$search}%");
            });
        }

        if ($kategori = trim((string) $request->input('kategori', ''))) {
            $query->where('kategori', $kategori);
        }

        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        if ($tanggalMulai && $tanggalSelesai) {
            $query->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai]);
        } elseif ($tanggalMulai) {
            $query->whereDate('tanggal', '>=', $tanggalMulai);
        } elseif ($tanggalSelesai) {
            $query->whereDate('tanggal', '<=', $tanggalSelesai);
        }

        $biaya = $query->paginate($limit)->withQueryString();

        return Inertia::render('BiayaOperasional/Index', [
            'biaya' => $biaya,
            'kategoriList' => BiayaOperasional::kategoriList(),
            'filters' => [
                'search' => $request->input('search', ''),
                'kategori' => $kategori,
                'tanggal_mulai' => $tanggalMulai ?? '',
                'tanggal_selesai' => $tanggalSelesai ?? '',
                'limit' => $limit,
            ],
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
        ]);
    }

    /**
     * Simpan biaya operasional baru.
     */
    public function store(StoreBiayaOperasionalRequest $request): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        $data = array_merge($validated, [
            'store_id' => ($storeId && $storeId !== 'all') ? $storeId : null,
        ]);

        BiayaOperasional::create($data);

        return back()->with('success', 'Biaya operasional berhasil ditambahkan.');
    }

    /**
     * Perbarui biaya operasional.
     */
    public function update(UpdateBiayaOperasionalRequest $request, BiayaOperasional $biayaOperasional): RedirectResponse
    {
        $biayaOperasional->update($request->validated());

        return back()->with('success', 'Data biaya operasional berhasil diperbarui.');
    }

    /**
     * Hapus biaya operasional.
     */
    public function destroy(BiayaOperasional $biayaOperasional): RedirectResponse
    {
        $biayaOperasional->delete();

        return back()->with('success', 'Data biaya operasional berhasil dihapus.');
    }
}
