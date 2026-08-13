<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /**
     * Relasi ke baris stok per-toko (tabel `stok`).
     */
    public function stoks(): HasMany
    {
        return $this->hasMany(Stok::class);
    }

    public function stokRiwayat(): HasMany
    {
        return $this->hasMany(StokRiwayat::class);
    }

    /**
     * Stok produk pada toko tertentu (default: toko aktif dari session).
     * Mengembalikan 0 bila belum ada baris stok.
     */
    public function stokDi(?int $storeId = null): int
    {
        if ($storeId === null) {
            $storeId = (int) (session('store_id') ?: 0);
        }

        if (! $storeId) {
            // Tanpa toko: total seluruh baris stok.
            return (int) $this->stoks()->sum('stok');
        }

        return (int) ($this->stoks()
            ->where('store_id', $storeId)
            ->value('stok') ?? 0);
    }

    /**
     * Stok minimum produk pada toko tertentu.
     */
    public function stokMinimumDi(?int $storeId = null): int
    {
        if ($storeId === null) {
            $storeId = (int) (session('store_id') ?: 0);
        }

        if (! $storeId) {
            return (int) ($this->stoks()->min('stok_minimum') ?? 0);
        }

        return (int) ($this->stoks()
            ->where('store_id', $storeId)
            ->value('stok_minimum') ?? 0);
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
     * Stok menipis pada toko tertentu jika stok <= stok_minimum.
     */
    public function isLowStock(?int $storeId = null): bool
    {
        return $this->stokDi($storeId) <= $this->stokMinimumDi($storeId);
    }

    /**
     * Harga jual final setelah diskon (jika diskon berupa nominal).
     */
    public function hargaFinal(): float
    {
        return (float) $this->harga_jual - (float) $this->diskon;
    }

    /**
     * Ambil (atau buat) baris stok untuk toko tertentu.
     */
    protected function stokRow(?int $storeId = null): Stok
    {
        $resolved = $storeId ?: (int) (session('store_id') ?: 0) ?: null;

        return $this->stoks()->firstOrCreate(
            ['store_id' => $resolved],
            ['stok' => 0, 'stok_minimum' => 0],
        );
    }

    /**
     * Tambah stok per-toko + catat riwayat stok (tipe: masuk).
     */
    public function tambahStok(int $qty, ?string $keterangan = null, ?int $storeId = null): void
    {
        $row = $this->stokRow($storeId);
        $row->increment('stok', $qty);

        StokRiwayat::create([
            'produk_id' => $this->id,
            'tipe' => 'masuk',
            'qty' => $qty,
            'tanggal' => now()->toDateString(),
            'keterangan' => $keterangan,
            'store_id' => $row->store_id,
        ]);
    }

    /**
     * Kurangi stok per-toko + catat riwayat stok (tipe: keluar).
     *
     * Stok tidak boleh negatif: bila qty melebihi stok, hanya dikurangi
     * sebatas stok tersisa (sisa 0) agar mutasi tetap konsisten.
     */
    public function kurangiStok(int $qty, ?string $keterangan = null, ?int $storeId = null): void
    {
        $row = $this->stokRow($storeId);
        $qtyEffectif = min($qty, $row->stok);

        if ($qtyEffectif > 0) {
            $row->decrement('stok', $qtyEffectif);
        }

        StokRiwayat::create([
            'produk_id' => $this->id,
            'tipe' => 'keluar',
            'qty' => $qtyEffectif,
            'tanggal' => now()->toDateString(),
            'keterangan' => $keterangan,
            'store_id' => $row->store_id,
        ]);
    }
}
