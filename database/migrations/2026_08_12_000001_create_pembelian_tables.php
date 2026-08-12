<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel header pembelian dari supplier + detail item pembelian.
     * Mengikuti pola transaksi/transaksi_detail:
     * - header menyimpan ringkasan (total, PPN, diskon, pembayaran).
     * - detail menyimpan snapshot nama barang & harga saat pembelian.
     */
    public function up(): void
    {
        Schema::create('pembelian', function (Blueprint $table) {
            $table->id();
            $table->string('no_pembelian')->unique();
            $table->string('no_faktur')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('nama_supplier')->nullable();
            $table->date('tanggal')->nullable();
            $table->boolean('terlampir_bukti_ppn')->default(false);
            $table->boolean('harga_jual_termasuk_ppn')->default(false);
            $table->decimal('nilai', 15, 2)->default(0);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('ppn_masukan', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->enum('jenis_bayar', ['tunai', 'kredit'])->default('tunai');
            $table->decimal('sisa_hutang', 15, 2)->default(0);
            $table->enum('status', ['draft', 'selesai', 'batal'])->default('selesai');
            $table->text('keterangan')->nullable();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('pembelian_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembelian_id')->constrained('pembelian')->cascadeOnDelete();
            $table->foreignId('produk_id')->nullable()->constrained('produk')->nullOnDelete();
            $table->string('nama_barang')->nullable();
            $table->integer('qty')->default(1);
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('diskon_item', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->date('tanggal_expired')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembelian_detail');
        Schema::dropIfExists('pembelian');
    }
};
