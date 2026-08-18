<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenerimaanAngsuranPotongGaji extends Model
{
    protected $table = 'penerimaan_angsuran_potong_gaji';

    protected $fillable = [
        'no_transaksi',
        'tgl_transaksi',
        'no_bukti',
        'register_tagihan_id',
        'no_register_tagihan',
        'anggota_id',
        'kode_anggota',
        'nama_anggota',
        'unit_kerja',
        'jabatan',
        'keterangan',
        'jumlah_potong',
        'total_terbayar_sebelum',
        'total_terbayar_sesudah',
        'sisa_piutang',
        'periode_gaji',
        'user_id',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'tgl_transaksi' => 'date',
            'jumlah_potong' => 'decimal:2',
            'total_terbayar_sebelum' => 'decimal:2',
            'total_terbayar_sesudah' => 'decimal:2',
            'sisa_piutang' => 'decimal:2',
        ];
    }

    public function registerTagihan(): BelongsTo
    {
        return $this->belongsTo(RegisterTagihanPiutang::class, 'register_tagihan_id');
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
     * Generate nomor transaksi: AGP{KODE}{YYYYMMDD}{urutan}.
     */
    public function generateNoTransaksi(?string $storeKode, string $tanggal): string
    {
        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $storeKode));
        if ($kode === '') {
            $kode = 'PUSAT';
        }
        $prefix = 'AGP' . $kode . str_replace('-', '', $tanggal);

        $last = static::where('no_transaksi', 'like', $prefix . '%')
            ->orderByDesc('no_transaksi')
            ->value('no_transaksi');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }
}
