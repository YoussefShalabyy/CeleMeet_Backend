<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->constrained('creator_profiles', 'user_id')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media_assets')->restrictOnDelete();
            
            $table->boolean('is_premium')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['creator_id', 'expires_at'], 'idx_creator_stories');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
