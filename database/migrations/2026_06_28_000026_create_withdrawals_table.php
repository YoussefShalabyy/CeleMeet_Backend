<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->restrictOnDelete(); // Prevent deleting user with pending withdrawal
            $table->unsignedBigInteger('amount_coins');
            
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->text('admin_note')->nullable();
            
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            
            $table->timestamps();

            $table->index(['creator_id', 'status'], 'idx_creator_withdrawals');
            $table->index(['status', 'created_at'], 'idx_pending_withdrawals');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
