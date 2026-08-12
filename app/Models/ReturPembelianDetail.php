<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturPembelianDetail extends Model
{
    protected $table = 'retur_pembelian_detail';

    protected $fillable = [
        'retur_pembelian_id',
        'produk_id',
        'nama_barang',
        'qty_retur',
        'harga_beli',
        'subtotal',
        'produk_tukar_id',
        'nama_barang_tukar',
        'qty_tukar',
    ];

    protected function casts(): array
    {
        return [
            'qty_retur' => 'integer',
            'harga_beli' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'qty_tukar' => 'integer',
        ];
    }

    public function returPembelian(): BelongsTo
    {
        return $this->belongsTo(ReturPembelian::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    public function produkTukar(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_tukar_id');
    }
}
