<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('likeable_type', 50); // Polymorphic
            $table->unsignedBigInteger('likeable_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'likeable_type', 'likeable_id'], 'uk_like'); // Prevents double-likes
            $table->index(['likeable_type', 'likeable_id'], 'idx_likeable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
