<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('anggota', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['anggota', 'karyawan'])->default('anggota');
            $table->string('nip')->unique();
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('pendidikan')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('divisi_pekerjaan')->nullable();
            $table->boolean('status_purna')->default(false);
            $table->date('tgl_terdaftar')->default(now()->toDateString());
            $table->date('tgl_pensiun')->nullable();
            $table->decimal('simpanan_pokok', 15, 2)->default(0);
            $table->decimal('simpanan_wajib', 15, 2)->default(0);
            $table->boolean('status_aktif')->default(true);
            $table->boolean('status_limit')->default(false);
            $table->decimal('limit_transaksi', 15, 2)->nullable();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggota');
    }
};
