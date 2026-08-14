<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenerimaanAngsuran extends Model
{
    protected $table = 'penerimaan_angsuran';

    protected $fillable = [
        'no_transaksi',
        'tgl_transaksi',
        'no_bukti',
        'no_faktur',
        'anggota_id',
        'kode_anggota',
        'nama_anggota',
        'alamat',
        'no_telp',
        'contact_person',
        'tgl_jatuh_tempo',
        'nilai_piutang',
        'retur_penjualan',
        'diskon_pembayaran',
        'total_harus_dibayar',
        'jumlah_bayar',
        'total_terbayar',
        'total_diskon',
        'kurang_bayar',
        'sisa_piutang',
        'keterangan',
        'user_id',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'tgl_transaksi' => 'date',
            'tgl_jatuh_tempo' => 'date',
            'nilai_piutang' => 'decimal:2',
            'retur_penjualan' => 'decimal:2',
            'diskon_pembayaran' => 'decimal:2',
            'total_harus_dibayar' => 'decimal:2',
            'jumlah_bayar' => 'decimal:2',
            'total_terbayar' => 'decimal:2',
            'total_diskon' => 'decimal:2',
            'kurang_bayar' => 'decimal:2',
            'sisa_piutang' => 'decimal:2',
        ];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
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
     * Generate nomor transaksi: PA{KODE}{YYYYMMDD}{urutan}.
     */
    public function generateNoTransaksi(?string $storeKode, string $tanggal): string
    {
        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $storeKode));
        if ($kode === '') {
            $kode = 'PUSAT';
        }
        $prefix = 'PA' . $kode . str_replace('-', '', $tanggal);

        $last = static::where('no_transaksi', 'like', $prefix . '%')
            ->orderByDesc('no_transaksi')
            ->value('no_transaksi');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }
}
