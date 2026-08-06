<?php

namespace App\Imports;

use App\Models\Supplier;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class SupplierImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnError, SkipsOnFailure
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

    public function model(array $row)
    {
        $nama = trim((string) ($row['nama_supplier'] ?? $row['nama'] ?? ''));

        if ($nama === '') {
            $this->skipped++;
            $this->errors[] = 'Nama Supplier kosong, dilewati.';

            return null;
        }

        $kode = trim((string) ($row['kode'] ?? ''));

        // Auto-generate kode bila kosong
        if ($kode === '') {
            $kode = (new Supplier())->generateKode();
        }

        // Lewati baris jika kode sudah ada (cegah duplikat)
        if (Supplier::where('kode', $kode)->exists()) {
            $this->skipped++;
            $this->errors[] = "Kode {$kode} sudah ada, dilewati.";

            return null;
        }

        $this->imported++;

        return new Supplier([
            'kode' => $kode,
            'nama' => $nama,
            'alamat' => trim((string) ($row['alamat'] ?? '')),
            'contact_person' => trim((string) ($row['contact_person'] ?? $row['kontak'] ?? '')),
            'no_telp' => trim((string) ($row['no_telp'] ?? $row['no_telp_hp'] ?? $row['telp'] ?? '')),
            'keterangan' => trim((string) ($row['keterangan'] ?? '')),
            'store_id' => $this->storeId,
        ]);
    }

    public function rules(): array
    {
        return [];
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
