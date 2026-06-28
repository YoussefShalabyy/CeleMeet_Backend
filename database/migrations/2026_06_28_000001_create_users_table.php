<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('username', 50)->unique()->nullable();
            $table->string('email', 150)->unique()->nullable();
            $table->string('phone', 20)->unique()->nullable();
            $table->string('password')->nullable();  // Nullable: social-only accounts have no password
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->enum('role', ['regular', 'celebrity', 'admin', 'moderator'])->default('regular');
            $table->boolean('is_banned')->default(false);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('email', 'idx_email');
            $table->index('username', 'idx_username');
            $table->index('role', 'idx_role');
            $table->index(['status', 'deleted_at'], 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
