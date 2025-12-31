<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds cost tracking and Jira integration fields
     * to project_time_estimates for better financial visibility.
     */
    public function up(): void
    {
        Schema::table('project_time_estimates', function (Blueprint $table) {
            // Hourly rate and cost tracking
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('assigned_to');
            $table->decimal('estimated_cost', 12, 2)->nullable()->after('hourly_rate');
            $table->decimal('actual_cost', 12, 2)->nullable()->after('estimated_cost');
            $table->decimal('cost_variance', 12, 2)->nullable()->after('actual_cost');
            $table->decimal('cost_variance_percentage', 8, 2)->nullable()->after('cost_variance');

            // Worklog sync tracking
            $table->decimal('synced_hours', 10, 2)->default(0)->after('actual_hours');
            $table->timestamp('last_worklog_sync')->nullable()->after('jira_issue_key');
            $table->integer('worklog_count')->default(0)->after('last_worklog_sync');

            // Priority and categorization
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium')->after('status');
            $table->string('category')->nullable()->after('priority');

            // Progress tracking
            $table->decimal('progress_percentage', 5, 2)->default(0)->after('variance_percentage');

            // Index for worklog sync
            $table->index(['jira_issue_key', 'last_worklog_sync']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_time_estimates', function (Blueprint $table) {
            $table->dropIndex(['jira_issue_key', 'last_worklog_sync']);

            $table->dropColumn([
                'hourly_rate',
                'estimated_cost',
                'actual_cost',
                'cost_variance',
                'cost_variance_percentage',
                'synced_hours',
                'last_worklog_sync',
                'worklog_count',
                'priority',
                'category',
                'progress_percentage',
            ]);
        });
    }
};
