<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    /**
     * Nama tabel eksplisit (hindari pluralisasi default).
     */
    protected $table = 'vouchers';

    protected $fillable = [
        'kode',
        'nama',
        'nominal',
        'barcode',
        'status',
        'tanggal_expired',
        'keterangan',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'tanggal_expired' => 'date',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Auto-generate kode voucher: VCH-0001, VCH-0002, dst.
     */
    public function generateKode(): string
    {
        $prefix = 'VCH';
        $last = static::where('kode', 'like', $prefix . '-%')
            ->orderByDesc('kode')
            ->value('kode');

        $next = $last ? ((int) substr($last, strlen($prefix) + 1)) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }

    /**
     * Auto-generate barcode unik (angka acak 13 digit, standar EAN-13):
     * prefix 890 (Indonesia) + 9 digit acak + check digit sederhana.
     */
    public function generateBarcode(): string
    {
        $not = $this->whereNotNull('barcode')->pluck('barcode');

        do {
            $base = '890' . str_pad((string) rand(0, 999999999), 9, '0', STR_PAD_LEFT);
            $sum = 0;
            foreach (str_split($base) as $i => $d) {
                $sum += (int) $d * ($i % 2 === 0 ? 1 : 3);
            }
            $check = (10 - ($sum % 10)) % 10;
            $barcode = $base . $check;
        } while ($not->contains($barcode));

        return $barcode;
    }

    /**
     * Status tampilan: kedaluwarsa jika tanggal_expired sudah lewat.
     */
    public function statusAktif(): string
    {
        if ($this->status === 'terpakai') {
            return 'terpakai';
        }

        if ($this->tanggal_expired && $this->tanggal_expired->lt(now())) {
            return 'kedaluwarsa';
        }

        return $this->status;
    }
}
