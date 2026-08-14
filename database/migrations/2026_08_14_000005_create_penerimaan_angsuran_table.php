<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel penerimaan_angsuran: penerimaan angsuran piutang dagang dari anggota/kustomer.
     * Menyimpan snapshot kustomer + perhitungan piutang (total, terbayar, sisa, diskon) + log user.
     */
    public function up(): void
    {
        Schema::create('penerimaan_angsuran', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi')->unique();
            $table->date('tgl_transaksi')->nullable();
            $table->string('no_bukti')->nullable();
            $table->string('no_faktur')->nullable();
            $table->foreignId('anggota_id')->nullable()->constrained('anggota')->nullOnDelete();
            $table->string('kode_anggota')->nullable();
            $table->string('nama_anggota')->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_telp')->nullable();
            $table->string('contact_person')->nullable();
            $table->date('tgl_jatuh_tempo')->nullable();
            $table->decimal('nilai_piutang', 15, 2)->default(0);
            $table->decimal('retur_penjualan', 15, 2)->default(0);
            $table->decimal('diskon_pembayaran', 15, 2)->default(0);
            $table->decimal('total_harus_dibayar', 15, 2)->default(0);
            $table->decimal('jumlah_bayar', 15, 2)->default(0);
            $table->decimal('total_terbayar', 15, 2)->default(0);
            $table->decimal('total_diskon', 15, 2)->default(0);
            $table->decimal('kurang_bayar', 15, 2)->default(0);
            $table->decimal('sisa_piutang', 15, 2)->default(0);
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
        Schema::dropIfExists('penerimaan_angsuran');
    }
};
