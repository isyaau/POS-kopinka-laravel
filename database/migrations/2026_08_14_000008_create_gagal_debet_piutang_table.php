<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel gagal_debet_piutang: pencatatan gagal debet tagihan piutang dagang
     * saat mencoba menarik dana via auto-debit/potong gaji tapi gagal (saldo tidak cukup, rekening tutup, dll).
     */
    public function up(): void
    {
        Schema::create('gagal_debet_piutang', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi')->unique();
            $table->date('tgl_gagal')->nullable();
            $table->string('no_bukti')->nullable();
            $table->foreignId('register_tagihan_id')->nullable()->constrained('register_tagihan_piutang')->nullOnDelete();
            $table->string('no_register_tagihan')->nullable();
            $table->foreignId('anggota_id')->nullable()->constrained('anggota')->nullOnDelete();
            $table->string('kode_anggota')->nullable();
            $table->string('nama_anggota')->nullable();
            $table->string('unit_kerja')->nullable();
            $table->string('jabatan')->nullable();
            $table->text('keterangan')->nullable();
            $table->decimal('jumlah_gagal_debet', 15, 2)->default(0);
            $table->string('alasan_gagal')->nullable(); // saldo tidak cukup, rekening tutup, nomor rekening salah, dll
            $table->string('status')->default('pending'); // pending, diproses, selesai, batal
            $table->date('tgl_followup')->nullable();
            $table->text('catatan_followup')->nullable();
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
        Schema::dropIfExists('gagal_debet_piutang');
    }
};
