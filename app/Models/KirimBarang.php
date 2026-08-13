<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KirimBarang extends Model
{
    protected $table = 'kirim_barang';

    protected $fillable = [
        'no_kirim',
        'tanggal',
        'store_asal_id',
        'nama_store_asal',
        'store_tujuan_id',
        'nama_store_tujuan',
        'status',
        'total_item',
        'total_nilai',
        'keterangan',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total_nilai' => 'decimal:2',
        ];
    }

    public function storeAsal(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_asal_id');
    }

    public function storeTujuan(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_tujuan_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(KirimBarangDetail::class);
    }

    /**
     * Auto-generate no kirim: KB{KODE_TOKO_ASAL}{YYYYMMDD}{0001}.
     *
     * Urutan dihitung per toko asal per hari. Bila $store null (mode
     * "Semua Toko"), kode fallback "ALL" dipakai.
     */
    public function generateNoKirim(?Store $store = null): string
    {
        $kode = $store?->kode ? strtoupper(preg_replace('/[^A-Z0-9]/', '', $store->kode)) : 'ALL';
        $prefix = 'KB' . $kode . now()->format('Ymd');

        $last = static::where('no_kirim', 'like', $prefix . '%')
            ->orderByDesc('no_kirim')
            ->value('no_kirim');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }
}
