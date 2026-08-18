<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengembalianLebihBayarPotongGaji extends Model
{
    protected $table = 'pengembalian_lebih_bayar_potong_gaji';

    protected $fillable = [
        'no_transaksi',
        'tgl_pengembalian',
        'no_bukti',
        'register_tagihan_id',
        'no_register_tagihan',
        'penerimaan_angsuran_potong_gaji_id',
        'no_transaksi_potong_gaji',
        'anggota_id',
        'kode_anggota',
        'nama_anggota',
        'unit_kerja',
        'jabatan',
        'keterangan',
        'jumlah_lebih_bayar',
        'jumlah_pengembalian',
        'metode_pengembalian',
        'status',
        'user_id',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'tgl_pengembalian' => 'date',
            'jumlah_lebih_bayar' => 'decimal:2',
            'jumlah_pengembalian' => 'decimal:2',
        ];
    }

    public function registerTagihan(): BelongsTo
    {
        return $this->belongsTo(RegisterTagihanPiutang::class, 'register_tagihan_id');
    }

    public function penerimaanAngsuranPotongGaji(): BelongsTo
    {
        return $this->belongsTo(PenerimaanAngsuranPotongGaji::class, 'penerimaan_angsuran_potong_gaji_id');
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
     * Generate nomor transaksi: PLB{KODE}{YYYYMMDD}{urutan}.
     */
    public function generateNoTransaksi(?string $storeKode, string $tanggal): string
    {
        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $storeKode));
        if ($kode === '') {
            $kode = 'PUSAT';
        }
        $prefix = 'PLB' . $kode . str_replace('-', '', $tanggal);

        $last = static::where('no_transaksi', 'like', $prefix . '%')
            ->orderByDesc('no_transaksi')
            ->value('no_transaksi');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }
}
