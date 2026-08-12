<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturPembelian extends Model
{
    protected $table = 'retur_pembelian';

    protected $fillable = [
        'no_retur',
        'pembelian_id',
        'no_pembelian_asal',
        'supplier_id',
        'nama_supplier',
        'tanggal',
        'tipe',
        'alasan',
        'total_retur',
        'status',
        'keterangan',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total_retur' => 'decimal:2',
        ];
    }

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class);
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
        return $this->hasMany(ReturPembelianDetail::class);
    }

    /**
     * Auto-generate no retur: RT{KODE_TOKO}{YYYYMMDD}{0001}.
     *
     * Urutan dihitung per toko per hari. Bila $store null (mode
     * "Semua Toko"), kode fallback "ALL" dipakai.
     */
    public function generateNoRetur(?Store $store = null): string
    {
        $kode = $store?->kode ? strtoupper(preg_replace('/[^A-Z0-9]/', '', $store->kode)) : 'ALL';
        $prefix = 'RT' . $kode . now()->format('Ymd');

        $last = static::where('no_retur', 'like', $prefix . '%')
            ->orderByDesc('no_retur')
            ->value('no_retur');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }
}
