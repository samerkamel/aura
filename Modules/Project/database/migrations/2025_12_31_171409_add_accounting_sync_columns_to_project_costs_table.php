<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds columns for syncing project costs to the accounting module.
     */
    public function up(): void
    {
        Schema::table('project_costs', function (Blueprint $table) {
            // Link to expense schedule for accounting integration
            $table->foreignId('expense_schedule_id')->nullable()->after('expense_id')
                ->constrained('expense_schedules')->nullOnDelete();

            // Sync status tracking
            $table->boolean('synced_to_accounting')->default(false)->after('expense_schedule_id');
            $table->timestamp('synced_at')->nullable()->after('synced_to_accounting');

            // Index for sync queries
            $table->index(['synced_to_accounting', 'cost_date']);
        });

        // Also add project linking to expense_schedules for reverse lookup
        Schema::table('expense_schedules', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('is_payroll_expense')
                ->constrained('projects')->nullOnDelete();
            $table->foreignId('project_cost_id')->nullable()->after('project_id')
                ->constrained('project_costs')->nullOnDelete();
            $table->boolean('is_project_expense')->default(false)->after('project_cost_id');

            // Index for project expense lookups
            $table->index(['is_project_expense', 'project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_costs', function (Blueprint $table) {
            $table->dropIndex(['synced_to_accounting', 'cost_date']);
            $table->dropForeign(['expense_schedule_id']);
            $table->dropColumn(['expense_schedule_id', 'synced_to_accounting', 'synced_at']);
        });

        Schema::table('expense_schedules', function (Blueprint $table) {
            $table->dropIndex(['is_project_expense', 'project_id']);
            $table->dropForeign(['project_id']);
            $table->dropForeign(['project_cost_id']);
            $table->dropColumn(['project_id', 'project_cost_id', 'is_project_expense']);
        });
    }
};
