<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    /**
     * Nama tabel eksplisit (hindari pluralisasi default).
     */
    protected $table = 'suppliers';

    protected $fillable = [
        'kode',
        'nama',
        'alamat',
        'contact_person',
        'no_telp',
        'keterangan',
        'store_id',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Auto-generate kode supplier jika kosong: SPL-0001, SPL-0002, dst.
     * Termasuk data arsip agar kode tidak duplikat saat restore.
     */
    public function generateKode(): string
    {
        $prefix = 'SPL';
        $last = static::withTrashed()
            ->where('kode', 'like', $prefix . '-%')
            ->orderByDesc('kode')
            ->value('kode');

        $next = $last ? ((int) substr($last, strlen($prefix) + 1)) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }
}
