<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_sessions', function (Blueprint $table): void {
            $table->id();
            
            $table->enum('provider', ['stream', 'agora', 'other'])->default('stream');
            $table->string('external_session_id', 255)->nullable();
            
            $table->foreignId('caller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('callee_id')->constrained('creator_profiles', 'user_id')->cascadeOnDelete();
            
            $table->enum('call_type', ['voice', 'video']); // Determines billing rate lookup
            
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            
            $table->unsignedBigInteger('rate_per_minute'); // Snapshot of creator's price at time of call
            $table->unsignedBigInteger('total_coins_charged')->default(0);
            
            $table->enum('status', ['initiated', 'in_progress', 'completed', 'missed', 'rejected', 'refunded'])->default('initiated');
            
            $table->timestamp('created_at')->useCurrent();

            $table->index(['caller_id', 'created_at'], 'idx_caller_calls');
            $table->index(['callee_id', 'created_at'], 'idx_callee_calls');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_sessions');
    }
};
