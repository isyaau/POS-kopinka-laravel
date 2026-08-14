<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restruktur konsinyi menjadi master-detail:
     * - `konsinyi`       : header (supplier, jenis, tanggal, no bukti/faktur, diskon, total, bayar, log user).
     * - `konsinyi_detail` : satu baris per produk (kode, nama, satuan, qty, harga beli/jual, subtotal).
     *
     * Tabel lama di-drop & dibuat ulang (data seeder bersifat demo & di-generate ulang).
     */
    public function up(): void
    {
        Schema::dropIfExists('konsinyi_detail');
        Schema::dropIfExists('konsinyi');

        Schema::create('konsinyi', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi')->unique();
            $table->enum('jenis', ['retur', 'pembayaran'])->default('pembayaran');
            $table->date('tgl_transaksi')->nullable();
            $table->string('no_bukti')->nullable();
            $table->string('no_faktur')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('kode_supplier')->nullable();
            $table->string('nama_supplier')->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_telp')->nullable();
            $table->string('contact_person')->nullable();
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('jumlah_bayar', 15, 2)->default(0);
            $table->decimal('kurang_bayar', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('konsinyi_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('konsinyi_id')->constrained('konsinyi')->cascadeOnDelete();
            $table->foreignId('produk_id')->nullable()->constrained('produk')->nullOnDelete();
            $table->string('kode_barang')->nullable();
            $table->string('nama_barang')->nullable();
            $table->string('satuan')->nullable();
            $table->integer('qty')->default(0);
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('konsinyi_detail');
        Schema::dropIfExists('konsinyi');
    }
};
