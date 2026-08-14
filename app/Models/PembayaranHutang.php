<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranHutang extends Model
{
    /**
     * Nama tabel eksplisit.
     */
    protected $table = 'pembayaran_hutang';

    protected $fillable = [
        'no_transaksi',
        'tgl_pembelian',
        'tgl_jatuh_tempo',
        'no_faktur',
        'kode_supplier',
        'nama_supplier',
        'alamat',
        'no_telp',
        'contact_person',
        'no_bukti',
        'tanggal_bayar',
        'nilai_pembelian',
        'retur_pembelian',
        'diskon_pembayaran',
        'total_harus_dibayar',
        'jumlah_bayar',
        'total_terbayar',
        'total_diskon',
        'kurang_bayar',
        'sisa_hutang',
        'supplier_id',
        'user_id',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'tgl_pembelian' => 'date',
            'tgl_jatuh_tempo' => 'date',
            'tanggal_bayar' => 'date',
            'nilai_pembelian' => 'decimal:2',
            'retur_pembelian' => 'decimal:2',
            'diskon_pembayaran' => 'decimal:2',
            'total_harus_dibayar' => 'decimal:2',
            'jumlah_bayar' => 'decimal:2',
            'total_terbayar' => 'decimal:2',
            'total_diskon' => 'decimal:2',
            'kurang_bayar' => 'decimal:2',
            'sisa_hutang' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
     * Generate nomor transaksi: PH{KODE}{YYYYMMDD}{urutan}.
     * Unik per toko + tanggal agar aman dijalankan berulang.
     */
    public function generateNoTransaksi(?string $storeKode, string $tanggal): string
    {
        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $storeKode));
        if ($kode === '') {
            $kode = 'PUSAT';
        }
        $prefix = 'PH' . $kode . str_replace('-', '', $tanggal);

        $last = static::where('no_transaksi', 'like', $prefix . '%')
            ->orderByDesc('no_transaksi')
            ->value('no_transaksi');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }
}
