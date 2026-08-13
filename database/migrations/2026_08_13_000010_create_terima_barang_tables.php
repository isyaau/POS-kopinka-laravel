<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menu "Terima Barang" — penerimaan barang dari supplier/konsinyor maupun
     * retur dari toko. Kolom `tipe` membedakan:
     * - 'konsinyasi' : barang titipan (Not Buy) — stok masuk, belum dibeli.
     * - 'retur_toko' : barang retur masuk dari toko lain.
     * Bedanya dengan pembelian: TIDAK ada field finansial (total/hutang/ppn)
     * karena barang hanya diterima (titipan), bukan dibeli.
     * Stok tetap ditambahkan ke tabel `stok` per-toko.
     */
    public function up(): void
    {
        Schema::create('terima_barang', function (Blueprint $table) {
            $table->id();
            $table->string('no_terima')->unique();
            $table->string('no_surat_jalan')->nullable();
            $table->enum('tipe', ['konsinyasi', 'retur_toko'])->default('konsinyasi');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('nama_supplier')->nullable();
            $table->date('tanggal')->nullable();
            $table->enum('status', ['draft', 'selesai', 'batal'])->default('selesai');
            $table->text('keterangan')->nullable();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('terima_barang_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('terima_barang_id')->constrained('terima_barang')->cascadeOnDelete();
            $table->foreignId('produk_id')->nullable()->constrained('produk')->nullOnDelete();
            $table->string('nama_barang')->nullable();
            $table->integer('qty')->default(1);
            $table->date('tanggal_expired')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terima_barang_detail');
        Schema::dropIfExists('terima_barang');
    }
};
