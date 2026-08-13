<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TerimaBarang extends Model
{
    /**
     * Nama tabel eksplisit (hindari pluralisasi default).
     */
    protected $table = 'terima_barang';

    protected $fillable = [
        'no_terima',
        'no_surat_jalan',
        'tipe',
        'supplier_id',
        'nama_supplier',
        'tanggal',
        'status',
        'keterangan',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(TerimaBarangDetail::class);
    }

    /**
     * Generate nomor terima: TB{STORE}{YYYYMMDD}{urutan}.
     * Unik per toko + tanggal agar aman saat dijalankan berulang.
     */
    public function generateNoTerima(?string $storeKode, string $tanggal): string
    {
        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $storeKode));
        if ($kode === '') {
            $kode = 'PUSAT';
        }
        $prefix = 'TB' . $kode . str_replace('-', '', $tanggal);

        $last = static::where('no_terima', 'like', $prefix . '%')
            ->orderByDesc('no_terima')
            ->value('no_terima');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }
}
