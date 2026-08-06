<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel voucher (kupon) dengan barcode & nominal.
     */
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();          // VCH-0001
            $table->string('nama');                    // Nama kupon
            $table->decimal('nominal', 15, 2)->default(0); // Nilai nominal kupon (Rp)
            $table->string('barcode')->unique();       // Kode barcode
            $table->enum('status', ['aktif', 'terpakai', 'kedaluwarsa'])->default('aktif');
            $table->date('tanggal_expired')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
