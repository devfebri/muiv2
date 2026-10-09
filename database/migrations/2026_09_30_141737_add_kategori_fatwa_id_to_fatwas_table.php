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
        Schema::table('fatwas', function (Blueprint $table) {
            $table->foreignId('kategori_fatwa_id')
                ->nullable()
                ->after('id')
                ->constrained('kategori_fatwas')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fatwas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kategori_fatwa_id');
        });
    }
};
