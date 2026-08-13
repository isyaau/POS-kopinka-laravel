<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menu "Stok Opname" — pencatatan hasil hitung fisik barang persediaan.
     * - `stok_sistem` = stok tercatat di tabel `stok` saat opname dibuat.
     * - `stok_fisik`  = hasil hitungan nyata.
     * - `selisih`     = stok_fisik - stok_sistem.
     * Saat status = 'selesai', stok riil di tabel `stok` disesuaikan ke stok_fisik
     * (reconcile) + dicatat ke `stok_riwayat`.
     */
    public function up(): void
    {
        Schema::create('stok_opname', function (Blueprint $table) {
            $table->id();
            $table->string('no_opname')->unique();
            $table->date('tanggal')->nullable();
            $table->enum('status', ['draft', 'selesai', 'batal'])->default('draft');
            $table->string('petugas')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stok_opname_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stok_opname_id')->constrained('stok_opname')->cascadeOnDelete();
            $table->foreignId('produk_id')->nullable()->constrained('produk')->nullOnDelete();
            $table->string('nama_barang')->nullable();
            $table->integer('stok_sistem')->default(0);
            $table->integer('stok_fisik')->default(0);
            $table->integer('selisih')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_opname_detail');
        Schema::dropIfExists('stok_opname');
    }
};
