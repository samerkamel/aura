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
            // Budget planning
            $table->decimal('planned_budget', 12, 2)->nullable()->after('description');
            $table->decimal('hourly_rate', 8, 2)->nullable()->after('planned_budget');
            $table->string('currency', 3)->default('EGP')->after('hourly_rate');

            // Timeline planning
            $table->date('planned_start_date')->nullable()->after('currency');
            $table->date('planned_end_date')->nullable()->after('planned_start_date');
            $table->date('actual_start_date')->nullable()->after('planned_end_date');
            $table->date('actual_end_date')->nullable()->after('actual_start_date');

            // Health tracking
            $table->enum('health_status', ['green', 'yellow', 'red'])->default('green')->after('actual_end_date');
            $table->decimal('current_health_score', 5, 2)->nullable()->after('health_status');

            // Project manager
            $table->foreignId('project_manager_id')->nullable()->after('customer_id')
                ->constrained('employees')->nullOnDelete();

            // Additional metadata
            $table->integer('estimated_hours')->nullable()->after('current_health_score');
            $table->enum('billing_type', ['fixed', 'hourly', 'milestone', 'retainer'])->default('hourly')->after('estimated_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['project_manager_id']);
            $table->dropColumn([
                'planned_budget',
                'hourly_rate',
                'currency',
                'planned_start_date',
                'planned_end_date',
                'actual_start_date',
                'actual_end_date',
                'health_status',
                'current_health_score',
                'project_manager_id',
                'estimated_hours',
                'billing_type',
            ]);
        });
    }
};
