<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegisterTagihanPiutangRequest;
use App\Http\Requests\UpdateRegisterTagihanPiutangRequest;
use App\Models\Anggota;
use App\Models\RegisterTagihanPiutang;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisterTagihanPiutangController extends Controller
{
    /**
     * Daftar register tagihan piutang dagang (potong gaji), di-scope sesuai toko aktif.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = RegisterTagihanPiutang::query()
            ->with(['anggota', 'user', 'store'])
            ->orderByDesc('tgl_tagihan')
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
                    ->orWhere('kode_anggota', 'ilike', "%{$search}%")
                    ->orWhere('unit_kerja', 'ilike', "%{$search}%")
                    ->orWhere('periode_potong', 'ilike', "%{$search}%");
            });
        }

        if ($anggotaId = $request->input('anggota_id')) {
            $query->where('anggota_id', $anggotaId);
        }

        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        if ($tanggalMulai && $tanggalSelesai) {
            $query->whereBetween('tgl_tagihan', [$tanggalMulai, $tanggalSelesai]);
        } elseif ($tanggalMulai) {
            $query->whereDate('tgl_tagihan', '>=', $tanggalMulai);
        } elseif ($tanggalSelesai) {
            $query->whereDate('tgl_tagihan', '<=', $tanggalSelesai);
        }

        $tagihan = $query->paginate($limit)->withQueryString();

        return Inertia::render('RegisterTagihanPiutang/Index', [
            'tagihan' => $tagihan,
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
     * Simpan register tagihan baru.
     */
    public function store(StoreRegisterTagihanPiutangRequest $request): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        $data = array_merge($validated, [
            'store_id' => ($storeId && $storeId !== 'all') ? $storeId : null,
            'user_id' => auth()->id(),
        ]);

        RegisterTagihanPiutang::create($data);

        return back()->with('success', 'Register tagihan piutang dagang (potong gaji) berhasil dicatat.');
    }

    /**
     * Perbarui register tagihan.
     */
    public function update(UpdateRegisterTagihanPiutangRequest $request, RegisterTagihanPiutang $registerTagihanPiutang): RedirectResponse
    {
        $registerTagihanPiutang->update($request->validated());

        return back()->with('success', 'Data register tagihan berhasil diperbarui.');
    }

    /**
     * Hapus register tagihan.
     */
    public function destroy(RegisterTagihanPiutang $registerTagihanPiutang): RedirectResponse
    {
        $registerTagihanPiutang->delete();

        return back()->with('success', 'Data register tagihan berhasil dihapus.');
    }
}
