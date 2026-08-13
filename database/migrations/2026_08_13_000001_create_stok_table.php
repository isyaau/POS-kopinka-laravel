<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Pindahkan stok & stok_minimum dari tabel `produk` ke tabel `stok`
     * yang per-toko (satu baris per produk_id + store_id).
     */
    public function up(): void
    {
        Schema::create('stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->integer('stok')->default(0);
            $table->integer('stok_minimum')->default(0);
            $table->timestamps();

            $table->unique(['produk_id', 'store_id']);
        });

        // Migrasi data awal: bagi stok produk ke toko asalnya (fallback toko pertama).
        $produk = DB::table('produk')
            ->select('id', 'store_id', 'stok', 'stok_minimum')
            ->get();

        if ($produk->isNotEmpty()) {
            $firstStoreId = DB::table('stores')->orderBy('id')->value('id');

            $rows = [];
            $now = now();

            foreach ($produk as $p) {
                $storeId = $p->store_id ?? $firstStoreId;
                if (! $storeId) {
                    continue;
                }

                $rows[] = [
                    'produk_id' => $p->id,
                    'store_id' => $storeId,
                    'stok' => (int) $p->stok,
                    'stok_minimum' => (int) $p->stok_minimum,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Insert per chunk agar aman untuk dataset besar (1000+ produk).
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('stok')->upsert(
                    $chunk,
                    ['produk_id', 'store_id'],
                    ['stok', 'stok_minimum', 'updated_at'],
                );
            }

            Log::info('Migrasi stok: ' . count($rows) . ' baris ke tabel stok.');
        }

        // Hapus kolom lama dari tabel produk.
        Schema::table('produk', function (Blueprint $table) {
            $table->dropColumn(['stok', 'stok_minimum']);
        });
    }

    /**
     * Reverse: kembalikan kolom ke produk (ambil stok toko pertama) lalu drop tabel stok.
     */
    public function down(): void
    {
        if (Schema::hasColumn('produk', 'stok') === false) {
            Schema::table('produk', function (Blueprint $table) {
                $table->integer('stok')->default(0);
                $table->integer('stok_minimum')->default(0);
            });
        }

        // Ambil stok dari toko pertama untuk dikembalikan ke produk.
        $stok = DB::table('stok')
            ->orderBy('store_id')
            ->get();

        foreach ($stok as $s) {
            DB::table('produk')
                ->where('id', $s->produk_id)
                ->update([
                    'stok' => (int) $s->stok,
                    'stok_minimum' => (int) $s->stok_minimum,
                ]);
        }

        Schema::dropIfExists('stok');
    }
};
