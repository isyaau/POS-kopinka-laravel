<?php

namespace App\Exports;

use App\Models\Voucher;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VoucherExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private array $filters = []) {}

    /**
     * Sumber data: semua voucher (atau kosong saat mode template).
     */
    public function query()
    {
        $query = Voucher::query()
            ->with('store')
            ->orderByDesc('created_at');

        if (! empty($this->filters['store_id']) && $this->filters['store_id'] !== 'all') {
            $query->where('store_id', $this->filters['store_id']);
        }

        // Mode template: hanya header (1 = whereRaw 1 = 0)
        if (! empty($this->filters['template'])) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Header kolom (sesuai bahasa UI).
     */
    public function headings(): array
    {
        return [
            'KODE',
            'NAMA',
            'NOMINAL',
            'BARCODE',
            'STATUS',
            'TANGGAL EXPIRED',
            'KETERANGAN',
        ];
    }

    /**
     * Mapping per baris.
     */
    public function map($voucher): array
    {
        return [
            $voucher->kode,
            $voucher->nama,
            (float) $voucher->nominal,
            $voucher->barcode,
            $voucher->status,
            $voucher->tanggal_expired?->format('d-m-Y'),
            $voucher->keterangan,
        ];
    }

    /**
     * Styling header (merah E53E3E).
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['argb' => 'E53E3E'],
                ],
            ],
        ];
    }
}
