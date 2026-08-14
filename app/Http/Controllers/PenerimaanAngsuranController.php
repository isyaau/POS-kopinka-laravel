<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePenerimaanAngsuranRequest;
use App\Http\Requests\UpdatePenerimaanAngsuranRequest;
use App\Models\Anggota;
use App\Models\PenerimaanAngsuran;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PenerimaanAngsuranController extends Controller
{
    /**
     * Daftar penerimaan angsuran piutang dagang, di-scope sesuai toko aktif.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = PenerimaanAngsuran::query()
            ->with(['anggota', 'user', 'store'])
            ->orderByDesc('tgl_transaksi')
            ->orderByDesc('id');

        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('no_transaksi', 'ilike', "%{$search}%")
                    ->orWhere('no_faktur', 'ilike', "%{$search}%")
                    ->orWhere('no_bukti', 'ilike', "%{$search}%")
                    ->orWhere('nama_anggota', 'ilike', "%{$search}%")
                    ->orWhere('kode_anggota', 'ilike', "%{$search}%");
            });
        }

        if ($anggotaId = $request->input('anggota_id')) {
            $query->where('anggota_id', $anggotaId);
        }

        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        if ($tanggalMulai && $tanggalSelesai) {
            $query->whereBetween('tgl_transaksi', [$tanggalMulai, $tanggalSelesai]);
        } elseif ($tanggalMulai) {
            $query->whereDate('tgl_transaksi', '>=', $tanggalMulai);
        } elseif ($tanggalSelesai) {
            $query->whereDate('tgl_transaksi', '<=', $tanggalSelesai);
        }

        $angsuran = $query->paginate($limit)->withQueryString();

        return Inertia::render('PenerimaanAngsuran/Index', [
            'angsuran' => $angsuran,
            'anggotas' => Anggota::orderBy('nama')->get(['id', 'nip', 'nama']),
            'filters' => [
                'search' => $request->input('search', ''),
                'anggota_id' => $anggotaId ?? '',
                'tanggal_mulai' => $tanggalMulai ?? '',
                'tanggal_selesai' => $tanggalSelesai ?? '',
                'limit' => $limit,
            ],
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
        ]);
    }

    /**
     * Simpan penerimaan angsuran baru.
     */
    public function store(StorePenerimaanAngsuranRequest $request): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        $data = array_merge($validated, [
            'store_id' => ($storeId && $storeId !== 'all') ? $storeId : null,
            'user_id' => auth()->id(),
        ]);

        PenerimaanAngsuran::create($data);

        return back()->with('success', 'Penerimaan angsuran piutang dagang berhasil dicatat.');
    }

    /**
     * Perbarui penerimaan angsuran.
     */
    public function update(UpdatePenerimaanAngsuranRequest $request, PenerimaanAngsuran $penerimaanAngsuran): RedirectResponse
    {
        $penerimaanAngsuran->update($request->validated());

        return back()->with('success', 'Data penerimaan angsuran berhasil diperbarui.');
    }

    /**
     * Hapus penerimaan angsuran.
     */
    public function destroy(PenerimaanAngsuran $penerimaanAngsuran): RedirectResponse
    {
        $penerimaanAngsuran->delete();

        return back()->with('success', 'Data penerimaan angsuran berhasil dihapus.');
    }
}
