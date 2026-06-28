<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('creator_profiles', 'user_id')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['follower_id', 'creator_id'], 'uk_follow'); // DB-level protection against double-following
            $table->index('creator_id', 'idx_creator_followers');
            $table->index('follower_id', 'idx_follower_following');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
