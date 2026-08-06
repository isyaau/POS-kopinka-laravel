<?php

namespace App\Imports;

use App\Models\Produk;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class ProdukImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnError, SkipsOnFailure
{
    use Importable;

    public int $imported = 0;
    public int $skipped = 0;
    public array $errors = [];

    protected ?int $storeId;

    public function __construct(?int $storeId = null)
    {
        $this->storeId = $storeId;
    }

    /**
     * Konversi nilai tanggal (serial Excel, DD-MM-YYYY, atau YYYY-MM-DD) menjadi Y-m-d.
     */
    protected function parseDate($value): ?string
    {
        if (! $value || $value === '') {
            return null;
        }

        try {
            $value = trim((string) $value);

            // Serial number Excel
            if (is_numeric($value)) {
                return (new \PhpOffice\PhpSpreadsheet\Shared\Date())
                    ->excelToDateTimeObject((float) $value)
                    ->format('Y-m-d');
            }

            // DD-MM-YYYY atau DD/MM/YYYY
            if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{2,4})$/', $value, $m)) {
                [$_, $d, $mo, $y] = $m;
                $y = strlen($y) === 2 ? '20' . $y : $y;

                return Carbon::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', (int) $y, (int) $mo, (int) $d))
                    ->format('Y-m-d');
            }

            // YYYY-MM-DD atau format lain yang dipahami Carbon
            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    public function model(array $row)
    {
        $nama = trim((string) ($row['nama_barang'] ?? ''));

        if ($nama === '') {
            $this->skipped++;
            $this->errors[] = 'Nama Barang kosong, dilewati.';

            return null;
        }

        $kode = trim((string) ($row['kode_barang'] ?? ''));

        // Auto-generate kode bila kosong
        if ($kode === '') {
            $kode = (new Produk())->generateKode();
        }

        // Lewati baris jika kode sudah ada (cegah duplikat)
        if (Produk::where('kode_barang', $kode)->exists()) {
            $this->skipped++;
            $this->errors[] = "Kode {$kode} sudah ada, dilewati.";

            return null;
        }

        $num = fn ($v, $default = 0) => ($v === null || $v === '') ? $default : (float) $v;

        $this->imported++;

        return new Produk([
            'kode_barang' => $kode,
            'nama_barang' => $nama,
            'kategori' => trim((string) ($row['kategori'] ?? '')),
            'satuan' => trim((string) ($row['satuan'] ?? '')),
            'no_rak' => trim((string) ($row['no_rak'] ?? '')),
            'harga_beli' => $num($row['harga_beli'] ?? null),
            'harga_jual' => $num($row['harga_jual'] ?? null),
            'diskon' => $num($row['diskon'] ?? null),
            'stok' => (int) $num($row['stok'] ?? null),
            'stok_minimum' => (int) $num($row['stok_minimum'] ?? null),
            'tanggal_expired' => $this->parseDate($row['tanggal_expired'] ?? null),
            'ppn' => $num($row['ppn'] ?? null),
            'store_id' => $this->storeId,
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_barang' => 'required',
        ];
    }

    public function onError(Throwable $e)
    {
        $this->errors[] = $e->getMessage();
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $row = $failure->row();
            $this->errors[] = "Baris {$row}: " . implode(', ', array_map('strval', $failure->errors()));
        }
    }
}
