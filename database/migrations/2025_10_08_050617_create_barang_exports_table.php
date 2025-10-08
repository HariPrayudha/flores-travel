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
        Schema::create('barang_exports', function (Blueprint $t) {
            $t->id();
            $t->string('kode')->unique();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->dateTime('exported_at');
            $t->unsignedInteger('jumlah_item')->default(0);
            $t->decimal('total_ongkir', 12, 2)->default(0);
            $t->decimal('total_lunas', 12, 2)->default(0);
            $t->decimal('total_belum_bayar', 12, 2)->default(0);
            $t->decimal('total_transfer', 12, 2)->default(0);
            $t->text('keterangan')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_exports');
    }
};
