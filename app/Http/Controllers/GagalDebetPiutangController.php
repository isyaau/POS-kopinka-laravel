<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGagalDebetPiutangRequest;
use App\Http\Requests\UpdateGagalDebetPiutangRequest;
use App\Models\Anggota;
use App\Models\GagalDebetPiutang;
use App\Models\RegisterTagihanPiutang;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GagalDebetPiutangController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = GagalDebetPiutang::query()
            ->with(['registerTagihan', 'anggota', 'user', 'store'])
            ->orderByDesc('tgl_gagal')
            ->orderByDesc('id');

        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('no_transaksi', 'ilike', "%{$search}%")
                    ->orWhere('no_register_tagihan', 'ilike', "%{$search}%")
                    ->orWhere('no_bukti', 'ilike', "%{$search}%")
                    ->orWhere('nama_anggota', 'ilike', "%{$search}%")
                    ->orWhere('kode_anggota', 'ilike', "%{$search}%")
                    ->orWhere('alasan_gagal', 'ilike', "%{$search}%");
            });
        }

        if ($anggotaId = $request->input('anggota_id')) {
            $query->where('anggota_id', $anggotaId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        if ($tanggalMulai && $tanggalSelesai) {
            $query->whereBetween('tgl_gagal', [$tanggalMulai, $tanggalSelesai]);
        } elseif ($tanggalMulai) {
            $query->whereDate('tgl_gagal', '>=', $tanggalMulai);
        } elseif ($tanggalSelesai) {
            $query->whereDate('tgl_gagal', '<=', $tanggalSelesai);
        }

        $gagalDebet = $query->paginate($limit)->withQueryString();

        return Inertia::render('GagalDebetPiutang/Index', [
            'gagalDebet' => $gagalDebet,
            'anggotas' => Anggota::orderBy('nama')->get(['id', 'nip', 'nama']),
            'registerTagihans' => RegisterTagihanPiutang::with('anggota:id,nip,nama')->orderByDesc('tgl_tagihan')->get(['id', 'no_transaksi', 'anggota_id', 'unit_kerja', 'jabatan', 'total_harus_dibayar', 'total_terbayar', 'sisa_piutang']),
            'filters' => [
                'search' => $request->input('search', ''),
                'anggota_id' => $anggotaId ?? '',
                'status' => $status ?? '',
                'tanggal_mulai' => $tanggalMulai ?? '',
                'tanggal_selesai' => $tanggalSelesai ?? '',
                'limit' => $limit,
            ],
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
        ]);
    }

    public function store(StoreGagalDebetPiutangRequest $request): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        // Ambil data register tagihan untuk auto-fill
        $registerTagihan = RegisterTagihanPiutang::find($validated['register_tagihan_id']);
        if ($registerTagihan) {
            $validated['no_register_tagihan'] = $registerTagihan->no_transaksi;
            $validated['anggota_id'] = $registerTagihan->anggota_id;
            $validated['kode_anggota'] = $registerTagihan->kode_anggota;
            $validated['nama_anggota'] = $registerTagihan->nama_anggota;
            $validated['unit_kerja'] = $registerTagihan->unit_kerja;
            $validated['jabatan'] = $registerTagihan->jabatan;
        }

        $data = array_merge($validated, [
            'store_id' => ($storeId && $storeId !== 'all') ? $storeId : null,
            'user_id' => auth()->id(),
        ]);

        GagalDebetPiutang::create($data);

        return back()->with('success', 'Gagal debet tagihan piutang berhasil dicatat.');
    }

    public function update(UpdateGagalDebetPiutangRequest $request, GagalDebetPiutang $gagalDebetPiutang): RedirectResponse
    {
        $gagalDebetPiutang->update($request->validated());

        return back()->with('success', 'Data gagal debet tagihan piutang berhasil diperbarui.');
    }

    public function destroy(GagalDebetPiutang $gagalDebetPiutang): RedirectResponse
    {
        $gagalDebetPiutang->delete();

        return back()->with('success', 'Data gagal debet tagihan piutang berhasil dihapus.');
    }
}
