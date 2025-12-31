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
        Schema::create('project_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('default_settings')->nullable(); // Default project settings
            $table->json('milestone_templates')->nullable(); // Array of milestone templates
            $table->json('risk_templates')->nullable(); // Common risks for this type
            $table->json('task_templates')->nullable(); // Standard tasks/estimates
            $table->json('team_structure')->nullable(); // Recommended team roles
            $table->integer('estimated_duration_days')->nullable();
            $table->decimal('estimated_budget', 12, 2)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('category');
        });

        // Add template_id to projects table
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable()->after('customer_id')->constrained('project_templates')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn('template_id');
        });

        Schema::dropIfExists('project_templates');
    }
};
