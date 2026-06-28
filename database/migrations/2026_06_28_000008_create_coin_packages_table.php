<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coin_packages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('coins');
            $table->decimal('price', 12, 2); // Real money, uses DECIMAL
            $table->char('currency', 3)->default('USD');
            $table->decimal('bonus_percentage', 5, 2)->default(0.00);
            $table->unsignedBigInteger('bonus_coins')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_packages');
    }
};
