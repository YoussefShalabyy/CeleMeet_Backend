<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('paid_message_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            
            $table->unsignedBigInteger('coins_returned');
            $table->enum('reason', ['no_reply', 'manual', 'other'])->default('no_reply');
            
            $table->timestamp('processed_at')->useCurrent(); // Immutable
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_refunds');
    }
};
