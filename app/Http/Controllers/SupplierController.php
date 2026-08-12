<?php

namespace App\Http\Controllers;

use App\Exports\SupplierExport;
use App\Http\Requests\ImportSupplierRequest;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Imports\SupplierImport;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SupplierController extends Controller
{
    /**
     * Menampilkan daftar supplier, di-scope sesuai toko aktif.
     */
    public function index(Request $request): Response
    {
        $storeId = session('store_id');

        // Limit data per halaman (whitelist, default 10)
        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        // Filter tab: aktif (default), arsip, semua
        $tab = $request->input('tab', 'aktif');
        $tab = in_array($tab, ['aktif', 'arsip', 'semua']) ? $tab : 'aktif';

        $query = Supplier::query()
            ->with('store')
            ->orderByDesc('created_at');

        if ($tab === 'arsip') {
            $query->onlyTrashed();
        } elseif ($tab === 'aktif') {
            $query->whereNull('deleted_at');
        }

        // Scope ke toko aktif (kecuali 'all' / pusat)
        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'ilike', "%{$search}%")
                    ->orWhere('kode', 'ilike', "%{$search}%")
                    ->orWhere('contact_person', 'ilike', "%{$search}%")
                    ->orWhere('no_telp', 'ilike', "%{$search}%");
            });
        }

        $suppliers = $query->paginate($limit)->withQueryString();

        return Inertia::render('Supplier/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $request->input('search', ''),
                'limit' => $limit,
                'tab' => $tab,
            ],
        ]);
    }

    /**
     * Simpan supplier baru.
     */
    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $storeId = session('store_id');
        $validated = $request->validated();

        $data = array_merge($validated, [
            'store_id' => ($storeId && $storeId !== 'all') ? $storeId : null,
        ]);

        Supplier::create($data);

        return back()->with('success', 'Supplier berhasil ditambahkan.');
    }

    /**
     * Perbarui supplier.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return back()->with('success', 'Data supplier berhasil diperbarui.');
    }

    /**
     * Arsipkan (soft delete) supplier — hilang dari listing aktif,
     * tetapi tetap bisa dikembalikan (restore).
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return back()->with('success', 'Supplier diarsipkan. Data dapat dipulihkan dari tab Arsip.');
    }

    /**
     * Pulihkan supplier yang diarsipkan.
     */
    public function restore(int $id): RedirectResponse
    {
        $supplier = Supplier::withTrashed()->findOrFail($id);
        $supplier->restore();

        return back()->with('success', 'Supplier berhasil dipulihkan kembali.');
    }

    /**
     * Export data supplier ke Excel (sesuai filter aktif).
     * Dengan ?template=1 menghasilkan template kosong (hanya heading).
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
            ? 'template-supplier.xlsx'
            : 'data-supplier-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new SupplierExport($filters), $namaFile);
    }

    /**
     * Import data supplier dari file Excel/CSV.
     */
    public function import(ImportSupplierRequest $request): RedirectResponse
    {
        $storeId = session('store_id');

        $import = new SupplierImport(($storeId && $storeId !== 'all') ? $storeId : null);
        Excel::import($import, $request->file('file'));

        $message = "Import selesai: {$import->imported} berhasil, {$import->skipped} dilewati.";

        if (! empty($import->errors)) {
            $detail = implode('; ', array_slice($import->errors, 0, 5));
            $message .= ' ' . $detail;
        }

        return back()->with('success', $message);
    }
}
