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
        Schema::table('projects', function (Blueprint $table) {
            // Only add columns that don't exist yet
            // Planning dates, estimated_hours already exist from original schema

            // Time estimates - budgeted hours
            $table->decimal('budgeted_hours', 10, 2)->nullable()->after('estimated_hours');

            // Resource planning
            $table->integer('required_team_size')->nullable()->after('budgeted_hours');
            $table->json('required_skills')->nullable()->after('required_team_size');

            // Priority and phase
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium')->after('required_skills');
            $table->enum('phase', ['initiation', 'planning', 'execution', 'monitoring', 'closure'])->default('initiation')->after('priority');

            // Progress
            $table->decimal('completion_percentage', 5, 2)->default(0)->after('phase');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'budgeted_hours',
                'required_team_size',
                'required_skills',
                'priority',
                'phase',
                'completion_percentage',
            ]);
        });
    }
};
