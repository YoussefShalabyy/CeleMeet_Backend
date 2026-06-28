<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->constrained('creator_profiles', 'user_id')->cascadeOnDelete();
            
            $table->enum('service_type', [
                'message',
                'voice_message',
                'voice_call',
                'video_call',
                'live_stream',
                'meet_greet',
                'group_call',
                'ai_chat'
            ]);
            
            $table->unsignedBigInteger('price_in_coins'); // Per-minute for calls, flat for messages
            $table->boolean('is_enabled')->default(true);
            $table->json('metadata')->nullable();
            
            $table->timestamps();

            $table->unique(['creator_id', 'service_type'], 'uk_service');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_services');
    }
};
