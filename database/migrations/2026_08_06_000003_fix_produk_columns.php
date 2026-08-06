<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Perbaikan aman (ALTER TABLE) — data yang sudah ada TIDAK dihapus:
     * - Menambahkan kolom supplier_id agar produk terhubung ke supplier.
     */
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            if (! Schema::hasColumn('produk', 'supplier_id')) {
                $table->foreignId('supplier_id')
                    ->nullable()
                    ->after('store_id')
                    ->constrained('suppliers')
                    ->nullOnDelete();
                $table->index('supplier_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
        });
    }
};
