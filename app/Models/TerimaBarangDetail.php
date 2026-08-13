<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerimaBarangDetail extends Model
{
    protected $table = 'terima_barang_detail';

    protected $fillable = [
        'terima_barang_id',
        'produk_id',
        'nama_barang',
        'qty',
        'tanggal_expired',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'tanggal_expired' => 'date',
        ];
    }

    public function terimaBarang(): BelongsTo
    {
        return $this->belongsTo(TerimaBarang::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }
}
