<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->string('owner_type', 50);
            $table->string('collection', 50);
            $table->string('provider', 50)->default('cloudinary');
            $table->string('provider_id', 255)->nullable();
            $table->string('url', 500);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();      // bytes
            $table->unsignedInteger('width')->nullable();        // pixels
            $table->unsignedInteger('height')->nullable();       // pixels
            $table->unsignedInteger('duration')->nullable();     // seconds (video/audio)
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Composite covers all polymorphic lookups
            $table->index(['owner_type', 'owner_id', 'collection'], 'idx_owner');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
