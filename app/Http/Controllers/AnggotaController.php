<?php

namespace App\Http\Controllers;

use App\Exports\AnggotaExport;
use App\Http\Requests\ImportAnggotaRequest;
use App\Http\Requests\StoreAnggotaRequest;
use App\Http\Requests\UpdateAnggotaRequest;
use App\Imports\AnggotaImport;
use App\Models\Anggota;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AnggotaController extends Controller
{
    /**
     * Menampilkan daftar anggota, di-scope sesuai toko aktif.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        // Limit data per halaman (whitelist, default 10)
        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = Anggota::query()
            ->with('store')
            ->orderByDesc('created_at');

        // Scope ke toko aktif (kecuali 'all' / pusat)
        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'ilike', "%{$search}%")
                    ->orWhere('nip', 'ilike', "%{$search}%")
                    ->orWhere('divisi_pekerjaan', 'ilike', "%{$search}%")
                    ->orWhere('no_hp', 'ilike', "%{$search}%");
            });
        }

        // Filter status (anggota/karyawan × aktif/purna/diblokir)
        $statusFilter = $request->input('status_filter');
        $allowedStatus = [
            'anggota_aktif', 'anggota_aktif_purna', 'anggota_diblokir', 'anggota_pasif_purna',
            'karyawan_aktif', 'karyawan_diblokir', 'karyawan_pasif_purna',
        ];
        if (in_array($statusFilter, $allowedStatus, true)) {
            $this->applyStatusFilter($query, $statusFilter);
        }

        // Filter rentang tanggal (tgl_terdaftar)
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        if ($tanggalMulai && $tanggalSelesai) {
            $query->whereBetween('tgl_terdaftar', [$tanggalMulai, $tanggalSelesai]);
        } elseif ($tanggalMulai) {
            $query->whereDate('tgl_terdaftar', '>=', $tanggalMulai);
        } elseif ($tanggalSelesai) {
            $query->whereDate('tgl_terdaftar', '<=', $tanggalSelesai);
        }

        $anggota = $query->paginate($limit)->withQueryString();

        return Inertia::render('Anggota/Index', [
            'anggota' => $anggota,
            'filters' => [
                'search' => $request->input('search', ''),
                'tanggal_mulai' => $tanggalMulai ?? '',
                'tanggal_selesai' => $tanggalSelesai ?? '',
                'status_filter' => $statusFilter ?? '',
                'limit' => $limit,
            ],
            'stores' => Store::orderBy('nama')->get(['id', 'kode', 'nama']),
        ]);
    }

    /**
     * Simpan anggota baru.
     */
    public function store(StoreAnggotaRequest $request): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        $data = array_merge($validated, [
            'store_id' => ($storeId && $storeId !== 'all') ? $storeId : null,
        ]);

        Anggota::create($data);

        return back()->with('success', 'Anggota berhasil ditambahkan.');
    }

    /**
     * Perbarui anggota.
     */
    public function update(UpdateAnggotaRequest $request, Anggota $anggota): RedirectResponse
    {
        $anggota->update($request->validated());

        return back()->with('success', 'Data anggota berhasil diperbarui.');
    }

    /**
     * Hapus anggota.
     */
    public function destroy(Anggota $anggota): RedirectResponse
    {
        $anggota->delete();

        return back()->with('success', 'Anggota berhasil dihapus.');
    }

    /**
     * Terapkan filter status ke query.
     *
     * Status mapping:
     * - *_aktif         → status_aktif = true,  status_purna = false, status_limit = false
     * - *_aktif_purna   → status_purna = true,  status_aktif = true
     * - *_diblokir      → status_limit = true
     * - *_pasif_purna   → status_purna = true,  status_aktif = false
     */
    protected function applyStatusFilter($query, string $filter): void
    {
        [$jenis, $kondisi] = explode('_', $filter, 2);

        $query->where('status', $jenis === 'karyawan' ? 'karyawan' : 'anggota');

        switch ($kondisi) {
            case 'aktif':
                $query->where('status_aktif', true)
                    ->where('status_purna', false)
                    ->where('status_limit', false);
                break;

            case 'aktif_purna':
                $query->where('status_purna', true)
                    ->where('status_aktif', true);
                break;

            case 'diblokir':
                $query->where('status_limit', true);
                break;

            case 'pasif_purna':
                $query->where('status_purna', true)
                    ->where('status_aktif', false);
                break;
        }
    }

    /**
     * Export data anggota ke Excel (sesuai filter aktif).
     * Dengan ?template=1 menghasilkan template kosong (hanya heading).
     */
    public function export(Request $request): BinaryFileResponse
    {
        $storeId = session('store_id');

        $filters = [
            'store_id' => $storeId,
            'search' => $request->input('search', ''),
            'tanggal_mulai' => $request->input('tanggal_mulai'),
            'tanggal_selesai' => $request->input('tanggal_selesai'),
            'status_filter' => $request->input('status_filter'),
            'template' => $request->boolean('template'),
        ];

        $namaFile = $request->boolean('template')
            ? 'template-anggota.xlsx'
            : 'data-anggota-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new AnggotaExport($filters), $namaFile);
    }

    /**
     * Import data anggota dari file Excel/CSV.
     */
    public function import(ImportAnggotaRequest $request): RedirectResponse
    {
        $storeId = session('store_id');

        $import = new AnggotaImport(($storeId && $storeId !== 'all') ? $storeId : null);
        Excel::import($import, $request->file('file'));

        $message = "Import selesai: {$import->imported} berhasil, {$import->skipped} dilewati.";

        if (! empty($import->errors)) {
            $detail = implode('; ', array_slice($import->errors, 0, 5));
            $message .= ' ' . $detail;
        }

        return back()->with('success', $message);
    }
}
