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
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('no_nota')->unique();
            $table->string('no_kasir')->nullable();
            $table->foreignId('anggota_id')->nullable()->constrained('anggota')->nullOnDelete();
            $table->string('nama_anggota')->nullable();
            $table->decimal('nilai', 15, 2)->default(0);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('jual', 15, 2)->default(0);
            $table->decimal('usaha', 15, 2)->default(0);
            $table->decimal('jasa', 15, 2)->default(0);
            $table->decimal('ppn', 15, 2)->default(0);
            $table->decimal('cash', 15, 2)->default(0);
            $table->decimal('qris', 15, 2)->default(0);
            $table->decimal('edc', 15, 2)->default(0);
            $table->decimal('voucher', 15, 2)->default(0);
            $table->decimal('piutang', 15, 2)->default(0);
            $table->date('tanggal')->nullable();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
