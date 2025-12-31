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
        Schema::create('project_risks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', ['technical', 'resource', 'schedule', 'budget', 'scope', 'external', 'other'])->default('other');
            $table->enum('probability', ['low', 'medium', 'high', 'very_high'])->default('medium');
            $table->enum('impact', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->integer('risk_score')->default(0); // Calculated: probability × impact
            $table->enum('status', ['identified', 'analyzing', 'mitigating', 'monitoring', 'resolved', 'accepted'])->default('identified');
            $table->text('mitigation_plan')->nullable();
            $table->text('contingency_plan')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('identified_date');
            $table->date('target_resolution_date')->nullable();
            $table->date('resolved_date')->nullable();
            $table->decimal('potential_cost_impact', 12, 2)->nullable();
            $table->integer('potential_delay_days')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'risk_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_risks');
    }
};
