<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KonsinyiDetail extends Model
{
    protected $table = 'konsinyi_detail';

    protected $fillable = [
        'konsinyi_id',
        'produk_id',
        'kode_barang',
        'nama_barang',
        'satuan',
        'qty',
        'harga_beli',
        'harga_jual',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga_beli' => 'decimal:2',
            'harga_jual' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function konsinyi(): BelongsTo
    {
        return $this->belongsTo(Konsinyi::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }
}
