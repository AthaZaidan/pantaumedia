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
        Schema::create('monitored_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('handle')->unique(); // contoh: @sahabat_negeri77
            $table->string('name');
            $table->string('platform', 30)->default('x'); // x, meta, tiktok
            $table->string('age')->default('Baru'); // misal: 14 hari
            $table->unsignedInteger('followers')->default(0);
            $table->unsignedInteger('following')->default(0);
            $table->string('posting_frequency')->nullable(); // misal: 180 post/hari
            $table->boolean('is_bot')->default(false);
            $table->decimal('bot_score', 5, 2)->default(0.00); // 0.00 - 100.00
            $table->jsonb('reasons')->nullable(); // Alasan heuristik deteksi bot
            $table->jsonb('topics')->nullable(); // Topik yang sering dibahas
            $table->jsonb('cluster')->nullable(); // Handle akun kawan dalam jaringan botnet
            $table->timestamps();

            $table->index(['platform', 'is_bot']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitored_accounts');
    }
};
