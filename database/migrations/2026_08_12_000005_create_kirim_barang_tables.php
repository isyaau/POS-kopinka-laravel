<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel header kirim barang antar unit toko + detail item kiriman.
     * - Header menyimpan ringkasan (no kirim, tanggal, asal/tujuan, status, total).
     * - Detail menyimpan snapshot barang yang dikirim (qty, harga beli, subtotal).
     */
    public function up(): void
    {
        Schema::create('kirim_barang', function (Blueprint $table) {
            $table->id();
            $table->string('no_kirim')->unique();
            $table->date('tanggal')->nullable();
            // Unit asal (pengirim)
            $table->foreignId('store_asal_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('nama_store_asal')->nullable();
            // Unit tujuan (penerima)
            $table->foreignId('store_tujuan_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('nama_store_tujuan')->nullable();
            $table->enum('status', ['draft', 'dikirim', 'selesai', 'batal'])->default('draft');
            $table->integer('total_item')->default(0);
            $table->decimal('total_nilai', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            // Scope toko aktif (toko yang membuat / mencatat kiriman)
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kirim_barang_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kirim_barang_id')->constrained('kirim_barang')->cascadeOnDelete();
            $table->foreignId('produk_id')->nullable()->constrained('produk')->nullOnDelete();
            $table->string('nama_barang')->nullable();
            $table->integer('qty')->default(1);
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kirim_barang_detail');
        Schema::dropIfExists('kirim_barang');
    }
};
