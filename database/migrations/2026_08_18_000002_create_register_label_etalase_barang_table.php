<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel register_label_etalase_barang: pencatatan registrasi label produk
     * yang dipajang di etalase/rak toko.
     */
    public function up(): void
    {
        Schema::create('register_label_etalase_barang', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi')->unique();
            $table->date('tgl_register')->nullable();
            $table->string('no_bukti')->nullable();
            $table->foreignId('produk_id')->nullable()->constrained('produk')->nullOnDelete();
            $table->string('kode_barang')->nullable();
            $table->string('nama_barang')->nullable();
            $table->string('kategori')->nullable();
            $table->string('satuan')->nullable();
            $table->string('no_rak')->nullable();
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->integer('jumlah_label')->default(0);
            $table->string('ukuran_label')->nullable(); // kecil, sedang, besar
            $table->text('keterangan')->nullable();
            $table->string('status')->default('draft'); // draft, tercetak, dipasang, batal
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('register_label_etalase_barang');
    }
};
