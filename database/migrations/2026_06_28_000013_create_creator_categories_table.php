<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_categories', function (Blueprint $table): void {
            $table->foreignId('creator_id')->constrained('creator_profiles', 'user_id')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            
            $table->primary(['creator_id', 'category_id']);
            $table->index('category_id', 'idx_category_creators');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_categories');
    }
};
