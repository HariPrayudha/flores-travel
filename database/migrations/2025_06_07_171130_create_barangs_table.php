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
        Schema::create('barangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang')->unique();
            $table->foreignId('user_id');
            $table->foreignId('user_update')->nullable();
            $table->foreignId('kota_asal');
            $table->foreignId('kota_tujuan');
            $table->text('deskripsi_barang');
            $table->string('nama_pengirim');
            $table->string('hp_pengirim')->nullable();
            $table->string('nama_penerima');
            $table->string('hp_penerima');
            $table->decimal('harga_awal');
            $table->decimal('harga_terbayar')->nullable();
            $table->date('tanggal_kirim')->nullable();
            $table->date('tanggal_terima')->nullable();
            $table->string('ttd_penerima')->nullable();
            $table->string('foto_penerima')->nullable();
            $table->string('bukti_transfer')->nullable();
            $table->string('bukti_pembatalan')->nullable();
            $table->boolean('paket_antar')->default(false);
            $table->text('alamat')->nullable();
            $table->string('status_bayar')->default("Belum Bayar");
            $table->string('status_barang')->default("Belum Diterima");
            $table->text('catatan_pengiriman')->nullable();
            $table->text('catatan_penerimaan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangs');
    }
};
