<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KirimBarangDetail extends Model
{
    protected $table = 'kirim_barang_detail';

    protected $fillable = [
        'kirim_barang_id',
        'produk_id',
        'nama_barang',
        'qty',
        'harga_beli',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga_beli' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function kirimBarang(): BelongsTo
    {
        return $this->belongsTo(KirimBarang::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }
}
