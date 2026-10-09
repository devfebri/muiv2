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
        Schema::create('konsultasis', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('email');
            $table->integer('usia');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->string('kab_kota');
            $table->string('kategori');
            $table->text('pertanyaan');
            $table->enum('status', ['pending', 'dijawab', 'ditolak'])->default('pending');
            $table->text('jawaban')->nullable();
            $table->foreignId('penjawab_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('konsultasis');
    }
};
