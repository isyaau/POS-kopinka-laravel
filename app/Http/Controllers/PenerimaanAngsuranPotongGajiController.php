<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePenerimaanAngsuranPotongGajiRequest;
use App\Http\Requests\UpdatePenerimaanAngsuranPotongGajiRequest;
use App\Models\Anggota;
use App\Models\PenerimaanAngsuranPotongGaji;
use App\Models\RegisterTagihanPiutang;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PenerimaanAngsuranPotongGajiController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = PenerimaanAngsuranPotongGaji::query()
            ->with(['registerTagihan', 'anggota', 'user', 'store'])
            ->orderByDesc('tgl_transaksi')
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
                    ->orWhere('unit_kerja', 'ilike', "%{$search}%")
                    ->orWhere('periode_gaji', 'ilike', "%{$search}%");
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

        return Inertia::render('PenerimaanAngsuranPotongGaji/Index', [
            'angsuran' => $angsuran,
            'anggotas' => Anggota::orderBy('nama')->get(['id', 'nip', 'nama']),
            'registerTagihans' => RegisterTagihanPiutang::orderByDesc('tgl_tagihan')->get(['id', 'no_transaksi', 'anggota_id', 'total_harus_dibayar', 'total_terbayar', 'sisa_piutang']),
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

    public function store(StorePenerimaanAngsuranPotongGajiRequest $request): RedirectResponse
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
            $validated['total_terbayar_sebelum'] = $registerTagihan->total_terbayar;
            $validated['total_terbayar_sesudah'] = $registerTagihan->total_terbayar + $validated['jumlah_potong'];
            $validated['sisa_piutang'] = max(0, $registerTagihan->total_harus_dibayar - $validated['total_terbayar_sesudah']);
        }

        $data = array_merge($validated, [
            'store_id' => ($storeId && $storeId !== 'all') ? $storeId : null,
            'user_id' => auth()->id(),
        ]);

        PenerimaanAngsuranPotongGaji::create($data);

        // Update register tagihan
        if ($registerTagihan) {
            $registerTagihan->increment('total_terbayar', $validated['jumlah_potong']);
            $registerTagihan->sisa_piutang = max(0, $registerTagihan->total_harus_dibayar - $registerTagihan->total_terbayar);
            $registerTagihan->save();
        }

        return back()->with('success', 'Penerimaan angsuran piutang dagang (potong gaji) berhasil dicatat.');
    }

    public function update(UpdatePenerimaanAngsuranPotongGajiRequest $request, PenerimaanAngsuranPotongGaji $penerimaanAngsuranPotongGaji): RedirectResponse
    {
        $penerimaanAngsuranPotongGaji->update($request->validated());

        return back()->with('success', 'Data penerimaan angsuran potong gaji berhasil diperbarui.');
    }

    public function destroy(PenerimaanAngsuranPotongGaji $penerimaanAngsuranPotongGaji): RedirectResponse
    {
        $penerimaanAngsuranPotongGaji->delete();

        return back()->with('success', 'Data penerimaan angsuran potong gaji berhasil dihapus.');
    }
}
