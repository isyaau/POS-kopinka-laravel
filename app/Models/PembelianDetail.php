<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembelianDetail extends Model
{
    protected $table = 'pembelian_detail';

    protected $fillable = [
        'pembelian_id',
        'produk_id',
        'nama_barang',
        'qty',
        'harga_beli',
        'diskon_item',
        'subtotal',
        'harga_jual',
        'tanggal_expired',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga_beli' => 'decimal:2',
            'diskon_item' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'harga_jual' => 'decimal:2',
            'tanggal_expired' => 'date',
        ];
    }

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }
}
