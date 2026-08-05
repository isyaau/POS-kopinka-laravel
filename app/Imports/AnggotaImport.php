<?php

namespace App\Imports;

use App\Models\Anggota;
use Carbon\Carbon;
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

class AnggotaImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnError, SkipsOnFailure
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
            if (preg_match('/^(\d{1,2})[-\\/](\d{1,2})[-\\/](\d{2,4})$/', $value, $m)) {
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
        $status = in_array(strtolower(trim((string) ($row['status'] ?? 'anggota'))), ['karyawan', 'anggota', 'k'])
            ? (strtolower(trim((string) $row['status'])) === 'karyawan' || strtolower(trim((string) $row['status'])) === 'k' ? 'karyawan' : 'anggota')
            : 'anggota';

        $nip = trim((string) ($row['nip'] ?? ''));

        // Auto-generate NIP bila kosong
        if ($nip === '') {
            $model = new Anggota(['status' => $status]);
            $nip = $model->generateNip();
        }

        // Lewati baris jika NIP sudah ada (cegah duplikat)
        if (Anggota::where('nip', $nip)->exists()) {
            $this->skipped++;
            $this->errors[] = "NIP {$nip} sudah ada, dilewati.";

            return null;
        }

        // Nilai boolean dari format "Ya"/"Tidak"/1/0
        $toBool = fn ($v, $default = false) => $v === null || $v === '' ? $default
            : in_array(strtolower(trim((string) $v)), ['1', 'ya', 'true', 'y', 'aktif', true]);

        $this->imported++;

        return new Anggota([
            'status' => $status,
            'nip' => $nip,
            'nama' => trim((string) ($row['nama'] ?? '')),
            'alamat' => trim((string) ($row['alamat'] ?? '')),
            'tempat_lahir' => trim((string) ($row['tempat_lahir'] ?? '')),
            'tanggal_lahir' => $this->parseDate($row['tanggal_lahir'] ?? null),
            'jenis_kelamin' => in_array(strtoupper(trim((string) ($row['jenis_kelamin'] ?? ''))), ['L', 'P']) ? strtoupper(trim((string) $row['jenis_kelamin'])) : null,
            'pendidikan' => trim((string) ($row['pendidikan'] ?? '')),
            'no_hp' => trim((string) ($row['no_hp'] ?? '')),
            'divisi_pekerjaan' => trim((string) ($row['divisi_pekerjaan'] ?? '')),
            'status_purna' => $toBool($row['status_purna'] ?? null),
            'tgl_terdaftar' => $this->parseDate($row['tgl_terdaftar'] ?? null) ?? now()->toDateString(),
            'tgl_pensiun' => $this->parseDate($row['tgl_pensiun'] ?? null),
            'simpanan_pokok' => (float) ($row['simpanan_pokok'] ?? 0),
            'simpanan_wajib' => (float) ($row['simpanan_wajib'] ?? 0),
            'status_aktif' => $toBool($row['status_aktif'] ?? null, true),
            'status_limit' => $toBool($row['status_limit'] ?? null),
            'limit_transaksi' => ($row['limit_transaksi'] ?? null) !== '' && $row['limit_transaksi'] !== null
                ? (float) $row['limit_transaksi']
                : null,
            'store_id' => $this->storeId,
        ]);
    }

    public function rules(): array
    {
        return [
            'nama' => 'required',
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
