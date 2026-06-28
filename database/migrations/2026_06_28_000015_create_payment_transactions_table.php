<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            
            $table->enum('provider', ['paymob', 'apple', 'google', 'stripe', 'other']);
            $table->string('provider_transaction_id', 255)->nullable();
            
            $table->decimal('amount', 12, 2); // Real money
            $table->char('currency', 3)->default('USD');
            $table->unsignedBigInteger('coins'); // Coins credited
            
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->json('raw_response')->nullable(); // Raw webhook payload for audit
            
            $table->timestamp('created_at')->useCurrent(); // Immutable

            $table->index(['provider', 'provider_transaction_id'], 'idx_provider_tx');
            $table->index(['user_id', 'created_at'], 'idx_user_payments');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
