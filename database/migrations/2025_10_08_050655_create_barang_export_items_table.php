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
        Schema::create('barang_export_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('export_id')->constrained('barang_exports')->cascadeOnDelete();
            $t->foreignId('barang_id')->constrained('barangs')->cascadeOnDelete();
            $t->string('kode_barang');
            $t->string('nama_penerima');
            $t->boolean('paket_antar')->default(false);
            $t->string('tujuan_text')->nullable();
            $t->string('deskripsi_barang')->nullable();
            $t->decimal('ongkos_kirim', 12, 2)->default(0);
            $t->string('status_bayar')->default('Belum Bayar');
            $t->boolean('status_terima')->default(false);
            $t->timestamp('diterima_at')->nullable();
            $t->foreignId('penerima_user_id')->nullable()->constrained('users')->nullOnDelete();

            $t->timestamps();
            $t->unique(['export_id', 'barang_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_export_items');
    }
};
