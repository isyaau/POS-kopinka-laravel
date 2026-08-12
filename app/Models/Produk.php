<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produk extends Model
{
    use SoftDeletes;

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
            // PPN adalah persentase (bukan uang) — tampil sebagai angka bulat,
            // tanpa 2 desimal di belakang koma.
            'ppn' => 'integer',
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
     * Auto-generate kode barang (barcode) jika kosong: 000001, 000002, dst.
     *
     * Hanya angka (digit) agar kompatibel dengan barcode scanner.
     * Urutan dihitung dari kode numerik terbesar yang sudah ada.
     */
    public function generateKode(): string
    {
        $last = static::withTrashed()
            ->where('kode_barang', '~', '^[0-9]+$')
            ->orderByRaw('CAST(kode_barang AS BIGINT) DESC')
            ->value('kode_barang');

        $next = $last !== null ? ((int) $last) + 1 : 1;

        return sprintf('%06d', $next);
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
