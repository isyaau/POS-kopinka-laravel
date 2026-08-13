<?php

namespace App\Imports;

use App\Models\Anggota;
use App\Models\Produk;
use App\Models\Store;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class TransaksiImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnError, SkipsOnFailure
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
     * Store tujuan import (untuk kode di no nota).
     */
    protected function resolveStore(): ?Store
    {
        return $this->storeId ? Store::find($this->storeId) : null;
    }

    /**
     * Normalisasi key row (heading Excel di-snake-case oleh Maatwebsite,
     * mis. "PPN.K" / "ID.AGT" bisa berubah jadi beragam bentuk).
     * Semua key diubah jadi lowercase tanpa karakter non-alphanumeric.
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
     * Konversi nilai tanggal (serial Excel, DD-MM-YYYY, atau YYYY-MM-DD) menjadi Y-m-d.
     */
    protected function parseDate($value): ?string
    {
        if (! $value || $value === '') {
            return now()->toDateString();
        }

        try {
            $value = trim((string) $value);

            if (is_numeric($value)) {
                return (new \PhpOffice\PhpSpreadsheet\Shared\Date())
                    ->excelToDateTimeObject((float) $value)
                    ->format('Y-m-d');
            }

            if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{2,4})$/', $value, $m)) {
                [$_, $d, $mo, $y] = $m;
                $y = strlen($y) === 2 ? '20' . $y : $y;

                return Carbon::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', (int) $y, (int) $mo, (int) $d))
                    ->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable) {
            return now()->toDateString();
        }
    }

    public function model(array $row)
    {
        $row = $this->normalize($row);

        $noNota = trim((string) ($row['nota'] ?? ''));

        // Auto-generate no nota bila kosong
        if ($noNota === '') {
            $noNota = (new Transaksi())->generateNoNota($this->resolveStore());
        }

        // Lewati baris jika no nota sudah ada
        if (Transaksi::where('no_nota', $noNota)->exists()) {
            $this->skipped++;
            $this->errors[] = "Nota {$noNota} sudah ada, dilewati.";

            return null;
        }

        // Resolve anggota dari ID.AGT (cocokkan dengan NIP anggota)
        $anggotaId = null;
        $idAgt = trim((string) ($row['idagt'] ?? ''));
        if ($idAgt !== '') {
            $anggota = Anggota::where('nip', $idAgt)->first();
            $anggotaId = $anggota?->id;
        }

        $namaAnggota = trim((string) ($row['namaanggota'] ?? '')) ?: null;

        $num = fn ($v, $default = 0) => ($v === null || $v === '') ? $default : (float) $v;

        $nilai = $num($row['nilai'] ?? null);
        $diskon = $num($row['diskon'] ?? null);
        $jual = $num($row['jual'] ?? null);
        $usaha = $num($row['usaha'] ?? null);
        $jasa = $num($row['jasa'] ?? null);
        $ppn = $num($row['ppnk'] ?? null);
        $cash = $num($row['cash'] ?? null);
        $qris = $num($row['qris'] ?? null);
        $edc = $num($row['edc'] ?? null);
        $voucher = $num($row['voucher'] ?? null);
        $piutang = $num($row['piutang'] ?? null);

        // Jika jual kosong, hitung dari nilai - diskon
        if ($jual === 0 && $nilai > 0) {
            $jual = max(0, $nilai - $diskon);
        }

        $transaksi = null;
        $detailCount = 0;

        DB::transaction(function () use (&$transaksi, &$detailCount, $row, $noNota, $anggotaId, $namaAnggota, $nilai, $diskon, $jual, $usaha, $jasa, $ppn, $cash, $qris, $edc, $voucher, $piutang, $num) {
            $transaksi = Transaksi::create([
                'no_nota' => $noNota,
                'no_kasir' => trim((string) ($row['no_kasir'] ?? '')) ?: null,
                'anggota_id' => $anggotaId,
                'nama_anggota' => $namaAnggota,
                'nilai' => $nilai,
                'diskon' => $diskon,
                'jual' => $jual,
                'usaha' => $usaha,
                'jasa' => $jasa,
                'ppn' => $ppn,
                'cash' => $cash,
                'qris' => $qris,
                'edc' => $edc,
                'voucher' => $voucher,
                'piutang' => $piutang,
                'tanggal' => $this->parseDate($row['tanggal'] ?? null),
                'store_id' => $this->storeId,
            ]);

            // Kolom item opsional: nama_barang_item, qty_item, harga_item
            $namaItem = trim((string) ($row['namabarangitem'] ?? ''));
            if ($namaItem !== '') {
                $produk = Produk::where('nama_barang', $namaItem)->first();

                $qty = max(1, (int) $num($row['qtyitem'] ?? 1));
                $harga = $num($row['hargaitem'] ?? 0);
                $diskonItem = $num($row['diskonitem'] ?? 0);

                TransaksiDetail::create([
                    'transaksi_id' => $transaksi->id,
                    'produk_id' => $produk?->id,
                    'nama_barang' => $namaItem,
                    'qty' => $qty,
                    'harga' => $harga,
                    'diskon_item' => $diskonItem,
                    'subtotal' => max(0, $qty * ($harga - $diskonItem)),
                ]);

                $detailCount++;

                // Kurangi stok produk bila ditemukan
                if ($produk && $produk->stokDi($this->storeId) >= $qty) {
                    $produk->kurangiStok($qty, "Transaksi {$noNota}", $this->storeId);
                }
            }
        });

        $this->imported++;

        return $transaksi;
    }

    public function rules(): array
    {
        return [
            'nota' => 'nullable',
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
