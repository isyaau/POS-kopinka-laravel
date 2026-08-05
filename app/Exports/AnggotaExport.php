<?php

namespace App\Exports;

use App\Models\Anggota;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AnggotaExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Query dasar dengan filter (search + rentang tanggal + scope toko).
     * Saat mode template, kembalikan query kosong agar hanya heading.
     */
    public function query(): Builder
    {
        if (! empty($this->filters['template'])) {
            return Anggota::query()->whereRaw('1 = 0');
        }

        $query = Anggota::query()
            ->orderByRaw("CASE WHEN status = 'karyawan' THEN 1 ELSE 0 END")
            ->orderByDesc('created_at');

        $storeId = $this->filters['store_id'] ?? null;
        if ($storeId && $storeId !== 'all') {
            $query->where('store_id', $storeId);
        }

        $search = trim((string) ($this->filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'ilike', "%{$search}%")
                    ->orWhere('nip', 'ilike', "%{$search}%")
                    ->orWhere('divisi_pekerjaan', 'ilike', "%{$search}%")
                    ->orWhere('no_hp', 'ilike', "%{$search}%");
            });
        }

        // Filter status (konsisten dengan controller)
        $statusFilter = $this->filters['status_filter'] ?? null;
        if ($statusFilter) {
            [$jenis, $kondisi] = explode('_', $statusFilter, 2);
            $query->where('status', $jenis === 'karyawan' ? 'karyawan' : 'anggota');

            match ($kondisi) {
                'aktif' => $query->where('status_aktif', true)->where('status_purna', false)->where('status_limit', false),
                'aktif_purna' => $query->where('status_purna', true)->where('status_aktif', true),
                'diblokir' => $query->where('status_limit', true),
                'pasif_purna' => $query->where('status_purna', true)->where('status_aktif', false),
                default => null,
            };
        }

        $mulai = $this->filters['tanggal_mulai'] ?? null;
        $selesai = $this->filters['tanggal_selesai'] ?? null;
        if ($mulai && $selesai) {
            $query->whereBetween('tgl_terdaftar', [$mulai, $selesai]);
        } elseif ($mulai) {
            $query->whereDate('tgl_terdaftar', '>=', $mulai);
        } elseif ($selesai) {
            $query->whereDate('tgl_terdaftar', '<=', $selesai);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Status',
            'NIP',
            'Nama',
            'Alamat',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Jenis Kelamin',
            'Pendidikan',
            'No HP',
            'Divisi Pekerjaan',
            'Status Purna',
            'Tgl Terdaftar',
            'Tgl Pensiun',
            'Simpanan Pokok',
            'Simpanan Wajib',
            'Status Aktif',
            'Status Limit',
            'Limit Transaksi',
        ];
    }

    public function map($anggota): array
    {
        return [
            $anggota->status === 'karyawan' ? 'Karyawan' : 'Anggota',
            $anggota->nip,
            $anggota->nama,
            $anggota->alamat,
            $anggota->tempat_lahir,
            $anggota->tanggal_lahir?->format('d-m-Y'),
            $anggota->jenis_kelamin,
            $anggota->pendidikan,
            $anggota->no_hp,
            $anggota->divisi_pekerjaan,
            $anggota->status_purna ? 'Ya' : 'Tidak',
            $anggota->tgl_terdaftar?->format('d-m-Y'),
            $anggota->tgl_pensiun?->format('d-m-Y'),
            $anggota->simpanan_pokok,
            $anggota->simpanan_wajib,
            $anggota->status_aktif ? 'Ya' : 'Tidak',
            $anggota->status_limit ? 'Ya' : 'Tidak',
            $anggota->limit_transaksi,
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
