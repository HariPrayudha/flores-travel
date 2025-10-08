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
        Schema::create('surat_jalan_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // pembuat (karani/admin)
            $table->unsignedInteger('items_count')->default(0);

            $table->decimal('total_ongkir', 15, 2)->default(0);
            $table->decimal('total_lunas', 15, 2)->default(0);
            $table->decimal('total_belum_bayar', 15, 2)->default(0);
            $table->decimal('total_transfer', 15, 2)->default(0);

            $table->json('items');
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });

        Schema::table('barangs', function (Blueprint $table) {
            $table->boolean('exported_supir')->default(false)->after('catatan_penerimaan');
            $table->timestamp('exported_at_supir')->nullable()->after('exported_supir');
            $table->foreignId('surat_jalan_batch_id')
                ->nullable()
                ->after('exported_at_supir')
                ->constrained('surat_jalan_batches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('surat_jalan_batch_id');
            $table->dropColumn(['exported_supir', 'exported_at_supir']);
        });
        Schema::dropIfExists('surat_jalan_batches');
    }
};
