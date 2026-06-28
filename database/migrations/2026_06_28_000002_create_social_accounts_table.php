<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50);      // VARCHAR for extensibility (not ENUM)
            $table->string('provider_id', 255);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['provider', 'provider_id'], 'uk_social');
            $table->index('user_id', 'idx_user_socials');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
