<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->unsignedBigInteger('amount'); // Always positive. Direction encoded in transaction_type.
            
            $table->enum('transaction_type', [
                'recharge',
                'message',
                'voice_message',
                'voice_call',
                'video_call',
                'subscription',
                'gift',
                'refund',
                'withdrawal',
                'admin_adjustment'
            ]);
            
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('pending');
            $table->unsignedBigInteger('reference_id')->nullable(); // Polymorphic
            $table->string('reference_type', 50)->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent(); // Immutable ledger

            $table->index(['user_id', 'created_at'], 'idx_user_tx');
            $table->index(['reference_type', 'reference_id'], 'idx_ref_tx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
