<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel pembayaran_hutang — mencatat pembayaran hutang ke supplier.
     *
     * Field (sesuai permintaan):
     * - no_transaksi       : nomor transaksi pembayaran (unik, auto: PH-KODE-YYYYMMDD-0001)
     * - tgl_pembelian      : tanggal pembelian (hutang awal)
     * - tgl_jatuh_tempo    : tanggal jatuh tempo
     * - no_faktur          : nomor faktur pembelian
     * - kode_supplier      : kode supplier
     * - nama_supplier      : nama supplier (snapshot)
     * - alamat             : alamat supplier (snapshot)
     * - no_telp            : no telepon supplier (snapshot)
     * - contact_person     : contact person supplier (snapshot)
     * - no_bukti           : nomor bukti pembayaran
     * - tanggal_bayar      : tanggal pembayaran
     * - nilai_pembelian    : nilai awal pembelian
     * - retur_pembelian    : nilai retur pembelian
     * - diskon_pembayaran  : diskon pembayaran
     * - total_harus_dibayar: total yang harus dibayar (setelah retur & diskon)
     * - jumlah_bayar       : jumlah dibayar pada transaksi ini
     * - total_terbayar     : total akumulasi terbayar
     * - total_diskon       : total akumulasi diskon
     * - kurang_bayar       : sisa yang kurang dibayar
     * - sisa_hutang        : sisa hutang akhir
     * - user_id            : log user yang input data
     * - supplier_id        : relasi ke supplier (nullable)
     * - store_id           : toko pemilik data (scoping)
     */
    public function up(): void
    {
        Schema::create('pembayaran_hutang', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi')->unique();
            $table->date('tgl_pembelian')->nullable();
            $table->date('tgl_jatuh_tempo')->nullable();
            $table->string('no_faktur')->nullable();
            $table->string('kode_supplier')->nullable();
            $table->string('nama_supplier')->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_telp')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('no_bukti')->nullable();
            $table->date('tanggal_bayar')->nullable();
            $table->decimal('nilai_pembelian', 15, 2)->default(0);
            $table->decimal('retur_pembelian', 15, 2)->default(0);
            $table->decimal('diskon_pembayaran', 15, 2)->default(0);
            $table->decimal('total_harus_dibayar', 15, 2)->default(0);
            $table->decimal('jumlah_bayar', 15, 2)->default(0);
            $table->decimal('total_terbayar', 15, 2)->default(0);
            $table->decimal('total_diskon', 15, 2)->default(0);
            $table->decimal('kurang_bayar', 15, 2)->default(0);
            $table->decimal('sisa_hutang', 15, 2)->default(0);
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
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
        Schema::dropIfExists('pembayaran_hutang');
    }
};
