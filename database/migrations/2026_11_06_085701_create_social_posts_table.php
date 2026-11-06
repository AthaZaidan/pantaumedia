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
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->foreignId('monitored_account_id')->nullable()->constrained('monitored_accounts')->nullOnDelete();
            $table->string('platform', 30); // meta, x, tiktok
            $table->string('author_name');
            $table->string('author_handle');
            $table->text('content');
            $table->boolean('is_bot')->default(false);
            $table->decimal('bot_score', 5, 2)->default(0.00); // persentase skor bot
            $table->string('sentiment', 20)->default('positive'); // positive, negative, neutral
            $table->decimal('sentiment_conf', 5, 2)->default(80.00); // confidence percentage
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('reposts')->default(0);
            $table->string('post_url')->nullable();
            $table->timestamp('posted_at')->useCurrent();
            $table->jsonb('metadata')->nullable(); // JSONB fleksibel untuk metadata spesifik per platform
            $table->timestamps();

            // Indexing optimal untuk query agregasi dan filter split-screen
            $table->index(['issue_id', 'platform', 'is_bot']);
            $table->index(['issue_id', 'sentiment']);
            $table->index('posted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
