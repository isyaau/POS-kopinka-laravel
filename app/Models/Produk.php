<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produk extends Model
{
    /**
     * Nama tabel eksplisit (hindari pluralisasi default menjadi "produks").
     */
    protected $table = 'produk';

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'kategori',
        'satuan',
        'no_rak',
        'harga_beli',
        'harga_jual',
        'diskon',
        'stok',
        'stok_minimum',
        'tanggal_expired',
        'ppn',
        'store_id',
        'supplier_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_expired' => 'date',
            'harga_beli' => 'decimal:2',
            'harga_jual' => 'decimal:2',
            'diskon' => 'decimal:2',
            'ppn' => 'decimal:2',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stokRiwayat(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StokRiwayat::class);
    }

    /**
     * Auto-generate kode barang jika kosong: BRK-0001, BRK-0002, dst.
     */
    public function generateKode(): string
    {
        $prefix = 'BRK';
        $last = static::where('kode_barang', 'like', $prefix . '-%')
            ->orderByDesc('kode_barang')
            ->value('kode_barang');

        $next = $last ? ((int) substr($last, strlen($prefix) + 1)) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }

    /**
     * Stok menipis jika stok <= stok_minimum.
     */
    public function isLowStock(): bool
    {
        return $this->stok <= $this->stok_minimum;
    }

    /**
     * Harga jual final setelah diskon (jika diskon berupa nominal).
     */
    public function hargaFinal(): float
    {
        return (float) $this->harga_jual - (float) $this->diskon;
    }

    /**
     * Tambah stok + catat riwayat stok (tipe: masuk).
     */
    public function tambahStok(int $qty, ?string $keterangan = null, ?int $storeId = null): void
    {
        $this->increment('stok', $qty);

        StokRiwayat::create([
            'produk_id' => $this->id,
            'tipe' => 'masuk',
            'qty' => $qty,
            'tanggal' => now()->toDateString(),
            'keterangan' => $keterangan,
            'store_id' => $storeId ?? $this->store_id,
        ]);
    }

    /**
     * Kurangi stok + catat riwayat stok (tipe: keluar).
     */
    public function kurangiStok(int $qty, ?string $keterangan = null, ?int $storeId = null): void
    {
        $this->decrement('stok', $qty);

        StokRiwayat::create([
            'produk_id' => $this->id,
            'tipe' => 'keluar',
            'qty' => $qty,
            'tanggal' => now()->toDateString(),
            'keterangan' => $keterangan,
            'store_id' => $storeId ?? $this->store_id,
        ]);
    }
}
