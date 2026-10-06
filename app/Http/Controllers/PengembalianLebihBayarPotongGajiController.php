<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengembalianLebihBayarPotongGajiRequest;
use App\Http\Requests\UpdatePengembalianLebihBayarPotongGajiRequest;
use App\Models\Anggota;
use App\Models\PengembalianLebihBayarPotongGaji;
use App\Models\PenerimaanAngsuranPotongGaji;
use App\Models\RegisterTagihanPiutang;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PengembalianLebihBayarPotongGajiController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = PengembalianLebihBayarPotongGaji::query()
            ->with(['registerTagihan', 'penerimaanAngsuranPotongGaji', 'anggota', 'user', 'store'])
            ->orderByDesc('tgl_pengembalian')
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
                    ->orWhere('metode_pengembalian', 'ilike', "%{$search}%");
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
            $query->whereBetween('tgl_pengembalian', [$tanggalMulai, $tanggalSelesai]);
        } elseif ($tanggalMulai) {
            $query->whereDate('tgl_pengembalian', '>=', $tanggalMulai);
        } elseif ($tanggalSelesai) {
            $query->whereDate('tgl_pengembalian', '<=', $tanggalSelesai);
        }

        $pengembalian = $query->paginate($limit)->withQueryString();

        return Inertia::render('PengembalianLebihBayarPotongGaji/Index', [
            'pengembalian' => $pengembalian,
            'anggotas' => Anggota::orderBy('nama')->get(['id', 'nip', 'nama']),
            'registerTagihans' => RegisterTagihanPiutang::with('anggota:id,nip,nama')->orderByDesc('tgl_tagihan')->get(['id', 'no_transaksi', 'anggota_id', 'unit_kerja', 'jabatan', 'total_harus_dibayar', 'total_terbayar', 'sisa_piutang']),
            'penerimaanAngsuranPotongGajis' => PenerimaanAngsuranPotongGaji::with('anggota:id,nip,nama')->orderByDesc('tgl_transaksi')->get(['id', 'no_transaksi', 'register_tagihan_id', 'anggota_id', 'jumlah_potong', 'total_terbayar_sebelum', 'total_terbayar_sesudah', 'sisa_piutang']),
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

    public function store(StorePengembalianLebihBayarPotongGajiRequest $request): RedirectResponse
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

        // Ambil data potong gaji jika dipilih
        if (!empty($validated['penerimaan_angsuran_potong_gaji_id'])) {
            $potongGaji = PenerimaanAngsuranPotongGaji::find($validated['penerimaan_angsuran_potong_gaji_id']);
            if ($potongGaji) {
                $validated['no_transaksi_potong_gaji'] = $potongGaji->no_transaksi;
            }
        }

        $data = array_merge($validated, [
            'store_id' => ($storeId && $storeId !== 'all') ? $storeId : null,
            'user_id' => auth()->id(),
        ]);

        PengembalianLebihBayarPotongGaji::create($data);

        return back()->with('success', 'Pengembalian lebih bayar potong gaji berhasil dicatat.');
    }

    public function update(UpdatePengembalianLebihBayarPotongGajiRequest $request, PengembalianLebihBayarPotongGaji $pengembalian): RedirectResponse
    {
        $pengembalian->update($request->validated());

        return back()->with('success', 'Data pengembalian lebih bayar potong gaji berhasil diperbarui.');
    }

    public function destroy(PengembalianLebihBayarPotongGaji $pengembalian): RedirectResponse
    {
        $pengembalian->delete();

        return back()->with('success', 'Data pengembalian lebih bayar potong gaji berhasil dihapus.');
    }
}
