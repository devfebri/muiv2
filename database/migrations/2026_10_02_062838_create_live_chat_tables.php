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
        // 1. FAQ Chatbot
        Schema::create('chat_faqs', function (Blueprint $table) {
            $table->id();
            $table->string('pertanyaan');
            $table->text('jawaban');
            $table->string('kategori')->nullable();
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Sesi Chat
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 64)->unique();
            $table->string('nama_pengunjung', 150);
            $table->string('email_pengunjung', 150)->nullable();
            $table->string('nohp_pengunjung', 30)->nullable();
            $table->string('topik', 150)->nullable();
            $table->string('status', 20)->default('menunggu'); // 'bot', 'menunggu', 'aktif', 'selesai'
            $table->unsignedInteger('antrian_nomor')->default(0);
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // 3. Pesan Chat
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained('chat_sessions')->cascadeOnDelete();
            $table->string('sender_type', 20); // 'pengunjung', 'operator', 'bot', 'system'
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('sender_name', 150);
            $table->text('pesan');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['chat_session_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');
        Schema::dropIfExists('chat_faqs');
    }
};
