<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel pengembalian_lebih_bayar_potong_gaji: pencatatan pengembalian dana
     * ketika potong gaji melebihi sisa piutang (lebih bayar).
     */
    public function up(): void
    {
        Schema::create('pengembalian_lebih_bayar_potong_gaji', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi')->unique();
            $table->date('tgl_pengembalian')->nullable();
            $table->string('no_bukti')->nullable();
            $table->foreignId('register_tagihan_id')->nullable()->constrained('register_tagihan_piutang')->nullOnDelete();
            $table->string('no_register_tagihan')->nullable();
            $table->foreignId('penerimaan_angsuran_potong_gaji_id')->nullable()->constrained('penerimaan_angsuran_potong_gaji')->nullOnDelete();
            $table->string('no_transaksi_potong_gaji')->nullable();
            $table->foreignId('anggota_id')->nullable()->constrained('anggota')->nullOnDelete();
            $table->string('kode_anggota')->nullable();
            $table->string('nama_anggota')->nullable();
            $table->string('unit_kerja')->nullable();
            $table->string('jabatan')->nullable();
            $table->text('keterangan')->nullable();
            $table->decimal('jumlah_lebih_bayar', 15, 2)->default(0);
            $table->decimal('jumlah_pengembalian', 15, 2)->default(0);
            $table->string('metode_pengembalian')->nullable(); // transfer, tunai, potong_gaji_berikutnya
            $table->string('status')->default('pending'); // pending, diproses, selesai, batal
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
        Schema::dropIfExists('pengembalian_lebih_bayar_potong_gaji');
    }
};
