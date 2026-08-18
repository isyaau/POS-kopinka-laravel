<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GagalDebetPiutang extends Model
{
    protected $table = 'gagal_debet_piutang';

    protected $fillable = [
        'no_transaksi',
        'tgl_gagal',
        'no_bukti',
        'register_tagihan_id',
        'no_register_tagihan',
        'anggota_id',
        'kode_anggota',
        'nama_anggota',
        'unit_kerja',
        'jabatan',
        'keterangan',
        'jumlah_gagal_debet',
        'alasan_gagal',
        'status',
        'tgl_followup',
        'catatan_followup',
        'user_id',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'tgl_gagal' => 'date',
            'tgl_followup' => 'date',
            'jumlah_gagal_debet' => 'decimal:2',
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
     * Generate nomor transaksi: GLD{KODE}{YYYYMMDD}{urutan}.
     */
    public function generateNoTransaksi(?string $storeKode, string $tanggal): string
    {
        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $storeKode));
        if ($kode === '') {
            $kode = 'PUSAT';
        }
        $prefix = 'GLD' . $kode . str_replace('-', '', $tanggal);

        $last = static::where('no_transaksi', 'like', $prefix . '%')
            ->orderByDesc('no_transaksi')
            ->value('no_transaksi');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }
}
