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
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->integer('rank')->default(1);
            $table->unsignedInteger('total_posts')->default(0);
            $table->decimal('human_pct', 5, 2)->default(50.00);
            $table->decimal('bot_pct', 5, 2)->default(50.00);
            $table->decimal('pos_pct', 5, 2)->default(50.00);
            $table->decimal('neg_pct', 5, 2)->default(50.00);
            $table->string('trend', 20)->default('stable'); // up, stable, down
            $table->jsonb('keywords')->nullable(); // Kata kunci pencarian bot/crawler
            $table->jsonb('platforms')->nullable(); // Statistik breakdown per platform (meta, x, tiktok)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
