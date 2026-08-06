<?php

namespace App\Exports;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupplierExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Query dasar dengan filter (search + scope toko).
     * Saat mode template, kembalikan query kosong agar hanya heading.
     */
    public function query(): Builder
    {
        if (! empty($this->filters['template'])) {
            return Supplier::query()->whereRaw('1 = 0');
        }

        $query = Supplier::query()->orderByDesc('created_at');

        $storeId = $this->filters['store_id'] ?? null;
        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        $search = trim((string) ($this->filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'ilike', "%{$search}%")
                    ->orWhere('kode', 'ilike', "%{$search}%")
                    ->orWhere('contact_person', 'ilike', "%{$search}%")
                    ->orWhere('no_telp', 'ilike', "%{$search}%");
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Kode',
            'Nama Supplier',
            'Alamat',
            'Contact Person',
            'No Telp/HP',
            'Keterangan',
        ];
    }

    public function map($supplier): array
    {
        return [
            $supplier->kode,
            $supplier->nama,
            $supplier->alamat,
            $supplier->contact_person,
            $supplier->no_telp,
            $supplier->keterangan,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E53E3E'],
                ],
                'fontColor' => ['rgb' => 'FFFFFF'],
            ],
        ];
    }
}
