<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('creator_profiles', 'user_id')->cascadeOnDelete(); // Denormalized for performance
            $table->foreignId('subscriber_id')->constrained('users')->cascadeOnDelete();
            
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            
            $table->timestamps();

            $table->index(['creator_id', 'subscriber_id', 'status', 'expires_at'], 'idx_subscription_check'); // HOT PATH
            $table->index(['status', 'expires_at'], 'idx_expiring'); // Scheduler
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
