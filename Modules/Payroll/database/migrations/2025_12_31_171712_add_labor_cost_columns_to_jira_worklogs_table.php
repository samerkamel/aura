<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds labor cost persistence columns to jira_worklogs.
     * Labor costs are calculated once based on employee salary at the time
     * and stored for accurate historical tracking.
     */
    public function up(): void
    {
        Schema::table('jira_worklogs', function (Blueprint $table) {
            // Project association
            $table->foreignId('project_id')->nullable()->after('employee_id')
                ->constrained('projects')->nullOnDelete();

            // Salary snapshot at time of worklog calculation
            $table->decimal('employee_salary_at_time', 12, 2)->nullable()->after('time_spent_hours');
            $table->decimal('billable_hours_in_month', 8, 2)->nullable()->after('employee_salary_at_time');

            // Calculated labor cost (persisted)
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('billable_hours_in_month');
            $table->decimal('labor_cost', 12, 2)->nullable()->after('hourly_rate');
            $table->decimal('labor_cost_multiplier', 5, 2)->nullable()->after('labor_cost');

            // PM Overhead (20% of labor cost)
            $table->decimal('pm_overhead', 12, 2)->nullable()->after('labor_cost_multiplier');

            // Total cost (labor + PM overhead)
            $table->decimal('total_cost', 12, 2)->nullable()->after('pm_overhead');

            // Cost calculation status
            $table->boolean('cost_calculated')->default(false)->after('total_cost');
            $table->timestamp('cost_calculated_at')->nullable()->after('cost_calculated');

            // Indexes for cost queries
            $table->index(['project_id', 'worklog_started']);
            $table->index(['cost_calculated', 'worklog_started']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jira_worklogs', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'worklog_started']);
            $table->dropIndex(['cost_calculated', 'worklog_started']);

            $table->dropForeign(['project_id']);
            $table->dropColumn([
                'project_id',
                'employee_salary_at_time',
                'billable_hours_in_month',
                'hourly_rate',
                'labor_cost',
                'labor_cost_multiplier',
                'pm_overhead',
                'total_cost',
                'cost_calculated',
                'cost_calculated_at',
            ]);
        });
    }
};
