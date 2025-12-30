<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_health_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->date('snapshot_date');
            $table->decimal('health_score', 5, 2); // 0-100
            $table->decimal('budget_score', 5, 2)->nullable();
            $table->decimal('schedule_score', 5, 2)->nullable();
            $table->decimal('scope_score', 5, 2)->nullable();
            $table->decimal('quality_score', 5, 2)->nullable();
            $table->json('metrics')->nullable(); // Detailed breakdown
            $table->timestamps();

            $table->unique(['project_id', 'snapshot_date']);
            $table->index('snapshot_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_health_snapshots');
    }
};
