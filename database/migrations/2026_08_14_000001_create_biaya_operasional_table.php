<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buat tabel biaya_operasional untuk mencatat pengeluaran operasional koperasi.
     *
     * Field:
     * - tanggal     : tanggal transaksi/pengeluaran
     * - no_bukti    : nomor bukti (unik), di-generate otomatis bila kosong
     * - dari_unit   : unit/toko sumber pengeluaran (teks bebas, mis. "Kopinka Pusat")
     * - kategori    : kategori biaya (dropdown tetap)
     * - keterangan  : catatan tambahan
     * - jumlah      : nominal pengeluaran (decimal)
     * - store_id    : toko pemilik data (scoping per toko aktif)
     */
    public function up(): void
    {
        Schema::create('biaya_operasional', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->nullable();
            $table->string('no_bukti')->unique();
            $table->string('dari_unit')->nullable();
            $table->string('kategori')->nullable();
            $table->text('keterangan')->nullable();
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biaya_operasional');
    }
};
