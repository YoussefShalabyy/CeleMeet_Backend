<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->constrained('creator_profiles', 'user_id')->cascadeOnDelete();
            
            $table->enum('content_type', ['image', 'video', 'text']);
            $table->text('caption')->nullable();
            $table->enum('visibility', ['free', 'premium', 'followers_only'])->default('free');
            
            $table->unsignedBigInteger('likes_count')->default(0); // Cached counter
            $table->unsignedBigInteger('comments_count')->default(0); // Cached counter
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['creator_id', 'created_at'], 'idx_creator_posts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
