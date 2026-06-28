<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->constrained('creator_profiles', 'user_id')->cascadeOnDelete();
            
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('coins');
            $table->unsignedInteger('duration_days');
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();

            $table->index('creator_id', 'idx_creator_plans');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
