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
        Schema::create('project_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('depends_on_project_id')->constrained('projects')->onDelete('cascade');
            $table->enum('dependency_type', ['finish_to_start', 'start_to_start', 'finish_to_finish', 'start_to_finish'])->default('finish_to_start');
            $table->integer('lag_days')->default(0); // Days of lag/lead time
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'resolved', 'broken'])->default('active');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['project_id', 'depends_on_project_id']);
            $table->index(['project_id', 'status']);
        });

        // Milestone dependencies (within a project)
        Schema::create('project_milestone_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_id')->constrained('project_milestones')->onDelete('cascade');
            $table->foreignId('depends_on_milestone_id')->constrained('project_milestones')->onDelete('cascade');
            $table->enum('dependency_type', ['finish_to_start', 'start_to_start', 'finish_to_finish', 'start_to_finish'])->default('finish_to_start');
            $table->integer('lag_days')->default(0);
            $table->timestamps();

            $table->unique(['milestone_id', 'depends_on_milestone_id'], 'milestone_dependency_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_milestone_dependencies');
        Schema::dropIfExists('project_dependencies');
    }
};
