<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama');                           // Nama kategori
            $table->string('slug')->unique();                 // Slug URL-friendly
            $table->string('warna', 30)->default('#007f5f'); // Warna badge (hex)
            $table->text('deskripsi')->nullable();            // Deskripsi singkat
            $table->boolean('aktif')->default(true);          // Status aktif/nonaktif
            $table->unsignedInteger('urutan')->default(0);    // Urutan tampil
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategoris');
    }
};
