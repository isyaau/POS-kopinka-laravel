<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StokOpname extends Model
{
    protected $table = 'stok_opname';

    protected $fillable = [
        'no_opname',
        'tanggal',
        'status',
        'petugas',
        'keterangan',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(StokOpnameDetail::class);
    }

    /**
     * Generate nomor opname: SO{STORE}{YYYYMMDD}{urutan}.
     */
    public function generateNoOpname(?string $storeKode, string $tanggal): string
    {
        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $storeKode));
        if ($kode === '') {
            $kode = 'PUSAT';
        }
        $prefix = 'SO' . $kode . str_replace('-', '', $tanggal);

        $last = static::where('no_opname', 'like', $prefix . '%')
            ->orderByDesc('no_opname')
            ->value('no_opname');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }
}
