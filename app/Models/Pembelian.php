<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pembelian extends Model
{
    protected $table = 'pembelian';

    protected $fillable = [
        'no_pembelian',
        'no_faktur',
        'supplier_id',
        'nama_supplier',
        'tanggal',
        'terlampir_bukti_ppn',
        'harga_jual_termasuk_ppn',
        'nilai',
        'diskon',
        'subtotal',
        'ppn_masukan',
        'total',
        'jenis_bayar',
        'sisa_hutang',
        'status',
        'keterangan',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'terlampir_bukti_ppn' => 'boolean',
            'harga_jual_termasuk_ppn' => 'boolean',
            'nilai' => 'decimal:2',
            'diskon' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'ppn_masukan' => 'decimal:2',
            'total' => 'decimal:2',
            'sisa_hutang' => 'decimal:2',
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
        return $this->hasMany(PembelianDetail::class);
    }

    /**
     * Auto-generate no pembelian: PB{KODE_TOKO}{YYYYMMDD}{0001}.
     *
     * Urutan dihitung per toko per hari. Bila $store null (mode
     * "Semua Toko"), kode fallback "ALL" dipakai.
     */
    public function generateNoPembelian(?Store $store = null): string
    {
        $kode = $store?->kode ? strtoupper(preg_replace('/[^A-Z0-9]/', '', $store->kode)) : 'ALL';
        $prefix = 'PB' . $kode . now()->format('Ymd');

        $last = static::where('no_pembelian', 'like', $prefix . '%')
            ->orderByDesc('no_pembelian')
            ->value('no_pembelian');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }
}
