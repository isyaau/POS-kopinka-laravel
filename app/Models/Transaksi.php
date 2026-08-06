<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaksi extends Model
{
    protected $table = 'transaksi';

    protected $fillable = [
        'no_nota',
        'no_kasir',
        'anggota_id',
        'nama_anggota',
        'nilai',
        'diskon',
        'jual',
        'usaha',
        'jasa',
        'ppn',
        'cash',
        'qris',
        'edc',
        'voucher',
        'piutang',
        'tanggal',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nilai' => 'decimal:2',
            'diskon' => 'decimal:2',
            'jual' => 'decimal:2',
            'usaha' => 'decimal:2',
            'jasa' => 'decimal:2',
            'ppn' => 'decimal:2',
            'cash' => 'decimal:2',
            'qris' => 'decimal:2',
            'edc' => 'decimal:2',
            'voucher' => 'decimal:2',
            'piutang' => 'decimal:2',
        ];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransaksiDetail::class);
    }

    /**
     * Auto-generate no nota: TRX-YYYYMMDD-0001.
     */
    public function generateNoNota(): string
    {
        $prefix = 'TRX-' . now()->format('Ymd');
        $last = static::where('no_nota', 'like', $prefix . '-%')
            ->orderByDesc('no_nota')
            ->value('no_nota');

        $next = $last ? ((int) substr($last, strlen($prefix) + 1)) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }
}
