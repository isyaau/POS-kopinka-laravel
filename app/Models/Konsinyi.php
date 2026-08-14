<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Konsinyi extends Model
{
    /**
     * Nama tabel eksplisit.
     */
    protected $table = 'konsinyi';

    protected $fillable = [
        'no_transaksi',
        'jenis',
        'tgl_transaksi',
        'no_bukti',
        'no_faktur',
        'supplier_id',
        'kode_supplier',
        'nama_supplier',
        'alamat',
        'no_telp',
        'contact_person',
        'diskon',
        'total',
        'jumlah_bayar',
        'kurang_bayar',
        'keterangan',
        'user_id',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'tgl_transaksi' => 'date',
            'diskon' => 'decimal:2',
            'total' => 'decimal:2',
            'jumlah_bayar' => 'decimal:2',
            'kurang_bayar' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(KonsinyiDetail::class)->orderBy('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Generate nomor transaksi: KS{KODE}{YYYYMMDD}{urutan}.
     */
    public function generateNoTransaksi(?string $storeKode, string $tanggal): string
    {
        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $storeKode));
        if ($kode === '') {
            $kode = 'PUSAT';
        }
        $prefix = 'KS' . $kode . str_replace('-', '', $tanggal);

        $last = static::where('no_transaksi', 'like', $prefix . '%')
            ->orderByDesc('no_transaksi')
            ->value('no_transaksi');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }
}
