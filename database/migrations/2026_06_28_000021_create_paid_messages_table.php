<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paid_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('creator_profiles', 'user_id')->cascadeOnDelete();
            
            $table->string('external_channel_id', 255)->nullable(); // Provider-agnostic
            $table->string('external_message_id', 255)->nullable(); // Needed for status sync
            
            $table->enum('message_type', ['text', 'image', 'voice']);
            $table->text('content')->nullable();
            $table->foreignId('media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            
            $table->unsignedBigInteger('price_in_coins');
            $table->enum('status', ['sent', 'delivered', 'read', 'expired', 'refunded'])->default('sent');
            $table->timestamp('refund_eligible_until')->nullable();
            
            $table->timestamp('created_at')->useCurrent(); // Immutable

            $table->index(['refund_eligible_until', 'status'], 'idx_message_refund'); // Refund scheduler
            $table->index(['sender_id', 'created_at'], 'idx_sender_msgs'); // Sender history
            $table->index(['receiver_id', 'created_at'], 'idx_receiver_msgs'); // Inbox
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paid_messages');
    }
};
