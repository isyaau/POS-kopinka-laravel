<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiayaOperasional extends Model
{
    /**
     * Nama tabel eksplisit (hindari pluralisasi default).
     */
    protected $table = 'biaya_operasional';

    protected $fillable = [
        'tanggal',
        'no_bukti',
        'dari_unit',
        'kategori',
        'keterangan',
        'jumlah',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'decimal:2',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Daftar kategori biaya operasional yang tersedia.
     */
    public static function kategoriList(): array
    {
        return [
            'Gaji Karyawan',
            'Listrik',
            'Air',
            'Telepon & Internet',
            'Sewa Gedung',
            'ATK',
            'Transportasi',
            'Perawatan & Pemeliharaan',
            'Promosi & Marketing',
            'Pajak & Retribusi',
            'Konsumsi',
            'Bank & Admin',
            'Lainnya',
        ];
    }

    /**
     * Generate nomor bukti: OP{KODE}{YYYYMMDD}{urutan}.
     * Unik per toko + tanggal agar aman saat dijalankan berulang.
     */
    public function generateNoBukti(?string $storeKode, string $tanggal): string
    {
        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $storeKode));
        if ($kode === '') {
            $kode = 'PUSAT';
        }
        $prefix = 'OP' . $kode . str_replace('-', '', $tanggal);

        $last = static::where('no_bukti', 'like', $prefix . '%')
            ->orderByDesc('no_bukti')
            ->value('no_bukti');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }

    public function formatJumlah(): string
    {
        return 'Rp ' . number_format((float) $this->jumlah, 0, ',', '.');
    }
}
