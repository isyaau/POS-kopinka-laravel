<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perbaiki tabel pembelian yang sudah terlanjur dibuat dengan skema lama:
     * - jenis_bayar hanya tunai & kredit (uang muka & hutang dihapus).
     * - Kolom uang_muka dihapus (bila masih ada).
     *
     * Urutan penting:
     * 1. DROP constraint lama TERLEBIH DAHULU (agar nilai 'kredit' bisa dipakai).
     * 2. Normalisasi data lama: uang_muka & hutang → kredit.
     * 3. Hapus kolom uang_muka.
     * 4. Tambah constraint baru ('tunai', 'kredit').
     */
    public function up(): void
    {
        // 1. Lepas constraint lama agar data bisa di-update
        DB::statement('ALTER TABLE pembelian DROP CONSTRAINT IF EXISTS pembelian_jenis_bayar_check');

        // 2. Normalisasi data lama: uang_muka & hutang → kredit
        DB::table('pembelian')
            ->whereIn('jenis_bayar', ['uang_muka', 'hutang'])
            ->update(['jenis_bayar' => 'kredit']);

        // 3. Hapus kolom uang_muka bila masih ada (dari migrasi lama)
        if (Schema::hasColumn('pembelian', 'uang_muka')) {
            Schema::table('pembelian', function (Blueprint $table) {
                $table->dropColumn('uang_muka');
            });
        }

        // 4. Tambah constraint baru (PostgreSQL)
        DB::statement("ALTER TABLE pembelian ADD CONSTRAINT pembelian_jenis_bayar_check CHECK (jenis_bayar IN ('tunai', 'kredit'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan constraint lama (bila rollback)
        DB::statement('ALTER TABLE pembelian DROP CONSTRAINT IF EXISTS pembelian_jenis_bayar_check');
        DB::statement("ALTER TABLE pembelian ADD CONSTRAINT pembelian_jenis_bayar_check CHECK (jenis_bayar IN ('tunai', 'uang_muka', 'hutang'))");

        if (! Schema::hasColumn('pembelian', 'uang_muka')) {
            Schema::table('pembelian', function (Blueprint $table) {
                $table->decimal('uang_muka', 15, 2)->default(0);
            });
        }
    }
};
