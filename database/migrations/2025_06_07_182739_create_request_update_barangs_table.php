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
            $table->foreignId('kota_asal')->nullable();
            $table->foreignId('kota_tujuan')->nullable();
            $table->text('deskripsi_barang')->nullable();
            $table->string('nama_pengirim')->nullable();
            $table->string('hp_pengirim')->nullable();
            $table->string('nama_penerima')->nullable();
            $table->string('hp_penerima')->nullable();
            $table->decimal('harga_awal', 15, 2)->nullable();
            $table->decimal('harga_terbayar', 15, 2)->nullable();
            $table->string('status_bayar')->nullable();
            $table->text('alasan')->nullable();
            $table->string('status_update')->default('Pending');
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
