<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel konsinyi — mencatat retur & pembayaran barang titipan (konsinyi) ke supplier.
     *
     * Satu menu mencakup dua jenis transaksi (kolom `jenis`):
     * - 'retur'      : pengembalian barang konsinyi yang tidak terjual ke supplier.
     * - 'pembayaran' : pembayaran ke supplier atas barang konsinyi yang terjual.
     *
     * Field snapshot: supplier, produk, harga konsinyi (beli), harga jual,
     * diskon, total, jumlah bayar, kurang bayar, serta log user.
     */
    public function up(): void
    {
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
            $table->foreignId('produk_id')->nullable()->constrained('produk')->nullOnDelete();
            $table->string('kode_barang')->nullable();
            $table->string('nama_barang')->nullable();
            $table->string('satuan')->nullable();
            $table->integer('qty')->default(0);
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('jumlah_bayar', 15, 2)->default(0);
            $table->decimal('kurang_bayar', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
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
        Schema::dropIfExists('konsinyi');
    }
};
