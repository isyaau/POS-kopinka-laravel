<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokOpnameDetail extends Model
{
    protected $table = 'stok_opname_detail';

    protected $fillable = [
        'stok_opname_id',
        'produk_id',
        'nama_barang',
        'stok_sistem',
        'stok_fisik',
        'selisih',
    ];

    protected function casts(): array
    {
        return [
            'stok_sistem' => 'integer',
            'stok_fisik' => 'integer',
            'selisih' => 'integer',
        ];
    }

    public function stokOpname(): BelongsTo
    {
        return $this->belongsTo(StokOpname::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }
}
