<?php

namespace App\Http\Controllers;

use App\Exports\VoucherExport;
use App\Http\Requests\StoreVoucherRequest;
use App\Http\Requests\UpdateVoucherRequest;
use App\Imports\VoucherImport;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VoucherController extends Controller
{
    /**
     * Menampilkan daftar voucher (kupon), di-scope sesuai toko aktif.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = Voucher::query()
            ->with('store')
            ->orderByDesc('created_at');

        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('kode', 'ilike', "%{$search}%")
                    ->orWhere('nama', 'ilike', "%{$search}%")
                    ->orWhere('barcode', 'ilike', "%{$search}%");
            });
        }

        // Filter status
        if ($status = $request->input('status', '')) {
            if (in_array($status, ['aktif', 'terpakai', 'kedaluwarsa'])) {
                $query->where('status', $status);
            }
        }

        $vouchers = $query->paginate($limit)->withQueryString();

        return Inertia::render('Voucher/Index', [
            'vouchers' => $vouchers,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Simpan voucher baru.
     */
    public function store(StoreVoucherRequest $request): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        Voucher::create([
            ...$validated,
            'store_id' => ($storeId && $storeId !== 'all') ? $storeId : ($validated['store_id'] ?? null),
        ]);

        return back()->with('success', 'Voucher berhasil disimpan.');
    }

    /**
     * Perbarui voucher.
     */
    public function update(UpdateVoucherRequest $request, Voucher $voucher): RedirectResponse
    {
        $validated = $request->validated();

        $voucher->update($validated);

        return back()->with('success', "Voucher {$voucher->kode} berhasil diperbarui.");
    }

    /**
     * Hapus voucher.
     */
    public function destroy(Voucher $voucher): RedirectResponse
    {
        $voucher->delete();

        return back()->with('success', "Voucher {$voucher->kode} berhasil dihapus.");
    }

    /**
     * Export data voucher ke Excel.
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
            ? 'template-voucher.xlsx'
            : 'data-voucher-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new VoucherExport($filters), $namaFile);
    }

    /**
     * Import data voucher dari file Excel/CSV.
     */
    public function import(Request $request): RedirectResponse
    {
        $storeId = session('store_id');

        $import = new VoucherImport(($storeId && $storeId !== 'all') ? $storeId : null);
        Excel::import($import, $request->file('file'));

        $message = "Import selesai: {$import->imported} berhasil, {$import->skipped} dilewati.";

        if (! empty($import->errors)) {
            $detail = implode('; ', array_slice($import->errors, 0, 5));
            $message .= ' ' . $detail;
        }

        return back()->with('success', $message);
    }
}
