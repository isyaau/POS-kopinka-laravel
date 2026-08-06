<?php

namespace App\Exports;

use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransaksiExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
            return Transaksi::query()->whereRaw('1 = 0');
        }

        $query = Transaksi::query()->orderByDesc('tanggal');

        $storeId = $this->filters['store_id'] ?? null;
        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        $search = trim((string) ($this->filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('no_nota', 'ilike', "%{$search}%")
                    ->orWhere('nama_anggota', 'ilike', "%{$search}%")
                    ->orWhere('no_kasir', 'ilike', "%{$search}%");
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'NO KASIR',
            'NOTA',
            'ID.AGT',
            'NAMA ANGGOTA',
            'NILAI',
            'DISKON',
            'JUAL',
            'USAHA',
            'JASA',
            'PPN.K',
            'CASH',
            'QRIS',
            'EDC',
            'VOUCHER',
            'PIUTANG',
            'TANGGAL',
        ];
    }

    public function map($transaksi): array
    {
        return [
            $transaksi->no_kasir,
            $transaksi->no_nota,
            $transaksi->anggota_id,
            $transaksi->nama_anggota,
            $transaksi->nilai,
            $transaksi->diskon,
            $transaksi->jual,
            $transaksi->usaha,
            $transaksi->jasa,
            $transaksi->ppn,
            $transaksi->cash,
            $transaksi->qris,
            $transaksi->edc,
            $transaksi->voucher,
            $transaksi->piutang,
            $transaksi->tanggal?->format('d-m-Y'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E53E3E'],
                ],
            ],
        ];
    }
}
