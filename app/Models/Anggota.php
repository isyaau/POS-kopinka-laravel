<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anggota extends Model
{
    /**
     * Nama tabel eksplisit (hindari pluralisasi default menjadi "anggotas").
     */
    protected $table = 'anggota';

    protected $fillable = [
        'status',
        'nip',
        'nama',
        'alamat',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'pendidikan',
        'no_hp',
        'divisi_pekerjaan',
        'status_purna',
        'tgl_terdaftar',
        'tgl_pensiun',
        'simpanan_pokok',
        'simpanan_wajib',
        'status_aktif',
        'status_limit',
        'limit_transaksi',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tgl_terdaftar' => 'date',
            'tgl_pensiun' => 'date',
            'status_purna' => 'boolean',
            'status_aktif' => 'boolean',
            'status_limit' => 'boolean',
            'simpanan_pokok' => 'decimal:2',
            'simpanan_wajib' => 'decimal:2',
            'limit_transaksi' => 'decimal:2',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Auto-generate NIP jika kosong: ANG-0001 (anggota) / KRY-0001 (karyawan).
     */
    public function generateNip(): string
    {
        $prefix = $this->status === 'karyawan' ? 'KRY' : 'ANG';
        $last = static::where('nip', 'like', $prefix . '-%')
            ->orderByDesc('nip')
            ->value('nip');

        $next = $last ? ((int) substr($last, strlen($prefix) + 1)) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }

    public function statusLabel(): string
    {
        return $this->status === 'karyawan' ? 'Karyawan' : 'Anggota';
    }

    public function jenisKelaminLabel(): string
    {
        return $this->jenis_kelamin === 'L' ? 'Laki-laki'
            : ($this->jenis_kelamin === 'P' ? 'Perempuan' : '-');
    }
}
