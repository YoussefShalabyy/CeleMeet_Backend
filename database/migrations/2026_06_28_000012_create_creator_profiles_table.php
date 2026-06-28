<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_profiles', function (Blueprint $table): void {
            // PRIMARY KEY is user_id. This is a 1:1 extension of the users table.
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            
            $table->string('display_name', 100);
            $table->text('bio')->nullable();
            $table->foreignId('avatar_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->foreignId('cover_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            
            $table->boolean('verification_badge')->default(false);
            $table->unsignedBigInteger('followers_count')->default(0); // Cached counters
            $table->unsignedBigInteger('posts_count')->default(0);
            $table->unsignedBigInteger('premium_subscribers_count')->default(0);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_profiles');
    }
};
