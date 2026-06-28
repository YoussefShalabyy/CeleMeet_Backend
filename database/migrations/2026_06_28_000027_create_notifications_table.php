<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 100); // NotificationType enum string
            $table->string('title', 150);
            $table->text('body')->nullable();
            
            $table->string('entity_type', 50)->nullable(); // Polymorphic trigger
            $table->unsignedBigInteger('entity_id')->nullable();
            
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'read_at', 'created_at'], 'idx_user_notifications');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
