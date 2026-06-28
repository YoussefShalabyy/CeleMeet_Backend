<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes(); // Deleted comments are hidden, but record preserved

            $table->index(['post_id', 'created_at'], 'idx_post_comments');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
