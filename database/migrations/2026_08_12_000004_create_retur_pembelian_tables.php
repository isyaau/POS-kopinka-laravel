<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel header retur/tukar pembelian dari supplier + detail item retur.
     * - Header menyimpan ringkasan (no retur, pembelian asal, supplier, tipe, total).
     * - Detail menyimpan snapshot barang yang diretur + barang pengganti (bila tukar).
     */
    public function up(): void
    {
        Schema::create('retur_pembelian', function (Blueprint $table) {
            $table->id();
            $table->string('no_retur')->unique();
            $table->foreignId('pembelian_id')->nullable()->constrained('pembelian')->nullOnDelete();
            $table->string('no_pembelian_asal')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('nama_supplier')->nullable();
            $table->date('tanggal')->nullable();
            $table->enum('tipe', ['retur', 'tukar'])->default('retur');
            $table->text('alasan')->nullable();
            $table->decimal('total_retur', 15, 2)->default(0);
            $table->enum('status', ['draft', 'selesai', 'batal'])->default('selesai');
            $table->text('keterangan')->nullable();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('retur_pembelian_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_pembelian_id')->constrained('retur_pembelian')->cascadeOnDelete();
            $table->foreignId('produk_id')->nullable()->constrained('produk')->nullOnDelete();
            $table->string('nama_barang')->nullable();
            $table->integer('qty_retur')->default(1);
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            // Barang pengganti (hanya untuk tipe tukar)
            $table->foreignId('produk_tukar_id')->nullable()->constrained('produk')->nullOnDelete();
            $table->string('nama_barang_tukar')->nullable();
            $table->integer('qty_tukar')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retur_pembelian_detail');
        Schema::dropIfExists('retur_pembelian');
    }
};
