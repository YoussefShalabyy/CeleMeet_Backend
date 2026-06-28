<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refresh_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_id', 255)->nullable();
            $table->string('token', 512);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable(); // NULL = active
            $table->timestamp('created_at')->useCurrent();

            $table->unique('token', 'uk_token');          // O(1) token lookup
            $table->index('user_id', 'idx_user_tokens'); // Fast logout-all-devices
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
