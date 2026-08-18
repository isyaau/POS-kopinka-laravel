<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel penerimaan_angsuran_potong_gaji: pencatatan penerimaan angsuran piutang dagang
     * yang dibayar via potong gaji (salary deduction) - realisasi dari register tagihan.
     */
    public function up(): void
    {
        Schema::create('penerimaan_angsuran_potong_gaji', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi')->unique();
            $table->date('tgl_transaksi')->nullable();
            $table->string('no_bukti')->nullable();
            $table->foreignId('register_tagihan_id')->nullable()->constrained('register_tagihan_piutang')->nullOnDelete();
            $table->string('no_register_tagihan')->nullable();
            $table->foreignId('anggota_id')->nullable()->constrained('anggota')->nullOnDelete();
            $table->string('kode_anggota')->nullable();
            $table->string('nama_anggota')->nullable();
            $table->string('unit_kerja')->nullable();
            $table->string('jabatan')->nullable();
            $table->text('keterangan')->nullable();
            $table->decimal('jumlah_potong', 15, 2)->default(0);
            $table->decimal('total_terbayar_sebelum', 15, 2)->default(0);
            $table->decimal('total_terbayar_sesudah', 15, 2)->default(0);
            $table->decimal('sisa_piutang', 15, 2)->default(0);
            $table->string('periode_gaji')->nullable();
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
        Schema::dropIfExists('penerimaan_angsuran_potong_gaji');
    }
};
