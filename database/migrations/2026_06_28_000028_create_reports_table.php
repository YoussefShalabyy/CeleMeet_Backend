<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users');
            
            $table->string('reportable_type', 50); // Polymorphic
            $table->unsignedBigInteger('reportable_id');
            
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'resolved'])->default('pending');
            
            $table->timestamp('created_at')->useCurrent();

            $table->index(['reportable_type', 'reportable_id'], 'idx_reportable');
            $table->index(['status', 'created_at'], 'idx_report_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
