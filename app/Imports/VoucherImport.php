<?php

namespace App\Imports;

use App\Models\Voucher;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class VoucherImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    public int $imported = 0;
    public int $skipped = 0;
    public array $errors = [];

    public function __construct(private ?int $storeId = null) {}

    /**
     * Kumpulan baris → insert (skip duplikat).
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $row = $this->normalize($row->toArray());

            $kode = trim((string) ($row['kode'] ?? ''));
            $barcode = trim((string) ($row['barcode'] ?? ''));

            if (Voucher::where('kode', $kode)->exists() || ($barcode && Voucher::where('barcode', $barcode)->exists())) {
                $this->skipped++;
                continue;
            }

            Voucher::create([
                'kode' => $kode ?: (new Voucher())->generateKode(),
                'nama' => trim((string) ($row['nama'] ?? '')) ?: 'Kupon',
                'nominal' => (float) ($row['nominal'] ?? 0),
                'barcode' => $barcode ?: (new Voucher())->generateBarcode(),
                'status' => in_array($row['status'] ?? '', ['aktif', 'terpakai', 'kedaluwarsa']) ? $row['status'] : 'aktif',
                'tanggal_expired' => $this->parseDate($row['tanggalexpired'] ?? null),
                'keterangan' => $row['keterangan'] ?? null,
                'store_id' => $this->storeId,
            ]);
            $this->imported++;
        }
    }

    /**
     * Normalisasi key heading (case & simbol).
     */
    protected function normalize(array $row): array
    {
        $out = [];
        foreach ($row as $key => $value) {
            $out[preg_replace('/[^a-z0-9]/i', '', strtolower((string) $key))] = $value;
        }

        return $out;
    }

    /**
     * Parse tanggal (serial Excel / DD-MM-YYYY / YYYY-MM-DD).
     */
    protected function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        foreach (['d-m-Y', 'Y-m-d', 'd/m/Y'] as $format) {
            $d = \DateTime::createFromFormat($format, $value);
            if ($d) {
                return $d->format('Y-m-d');
            }
        }

        return null;
    }

    public function rules(): array
    {
        return [];
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->errors[] = 'Baris ' . $failure->row() . ': ' . implode(', ', $failure->errors());
            $this->skipped++;
        }
    }
}
