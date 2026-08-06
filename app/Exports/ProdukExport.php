<?php

namespace App\Exports;

use App\Models\Produk;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProdukExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
            return Produk::query()->whereRaw('1 = 0');
        }

        $query = Produk::query()->orderByDesc('created_at');

        $storeId = $this->filters['store_id'] ?? null;
        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        $search = trim((string) ($this->filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'ilike', "%{$search}%")
                    ->orWhere('kode_barang', 'ilike', "%{$search}%")
                    ->orWhere('kategori', 'ilike', "%{$search}%");
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID Barang',
            'Kode Barang',
            'Nama Barang',
            'Kategori',
            'Satuan',
            'No Rak',
            'Harga Beli',
            'Harga Jual',
            'Diskon',
            'Stok',
            'Stok Minimum',
            'Tanggal Expired',
            'PPN',
        ];
    }

    public function map($produk): array
    {
        return [
            $produk->id,
            $produk->kode_barang,
            $produk->nama_barang,
            $produk->kategori,
            $produk->satuan,
            $produk->no_rak,
            $produk->harga_beli,
            $produk->harga_jual,
            $produk->diskon,
            $produk->stok,
            $produk->stok_minimum,
            $produk->tanggal_expired?->format('d-m-Y'),
            $produk->ppn,
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
