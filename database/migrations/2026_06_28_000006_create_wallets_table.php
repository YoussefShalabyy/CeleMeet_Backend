<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('available_balance')->default(0);
            $table->unsignedBigInteger('held_balance')->default(0);    // Reserved during active calls
            $table->unsignedBigInteger('total_earned')->default(0);    // Lifetime creator earnings
            $table->unsignedBigInteger('total_spent')->default(0);     // Lifetime total spent
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
