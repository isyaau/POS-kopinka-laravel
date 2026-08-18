<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegisterLabelEtalaseBarang extends Model
{
    protected $table = 'register_label_etalase_barang';

    protected $fillable = [
        'no_transaksi',
        'tgl_register',
        'no_bukti',
        'produk_id',
        'kode_barang',
        'nama_barang',
        'kategori',
        'satuan',
        'no_rak',
        'harga_jual',
        'jumlah_label',
        'ukuran_label',
        'keterangan',
        'status',
        'user_id',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'tgl_register' => 'date',
            'harga_jual' => 'decimal:2',
            'jumlah_label' => 'integer',
        ];
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
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
     * Generate nomor transaksi: RLE{KODE}{YYYYMMDD}{urutan}.
     */
    public function generateNoTransaksi(?string $storeKode, string $tanggal): string
    {
        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $storeKode));
        if ($kode === '') {
            $kode = 'PUSAT';
        }
        $prefix = 'RLE' . $kode . str_replace('-', '', $tanggal);

        $last = static::where('no_transaksi', 'like', $prefix . '%')
            ->orderByDesc('no_transaksi')
            ->value('no_transaksi');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }
}
