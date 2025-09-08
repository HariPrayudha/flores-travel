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
        Schema::create('request_update_barangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id');
            $table->foreignId('user_id');
            $table->foreignId('kota_asal');
            $table->foreignId('kota_tujuan');
            $table->text('deskripsi_barang');
            $table->string('nama_pengirim');
            $table->string('hp_pengirim');
            $table->string('nama_penerima');
            $table->string('hp_penerima');
            $table->decimal('harga_awal');
            $table->decimal('harga_terbayar');
            $table->string('status_bayar')->default("Belum Bayar");
            $table->text('alasan')->nullable();
            $table->text('status_update')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_update_barangs');
    }
};
