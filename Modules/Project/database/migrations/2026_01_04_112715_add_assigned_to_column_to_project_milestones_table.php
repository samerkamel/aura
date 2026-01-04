<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_milestones', function (Blueprint $table) {
            // Add assigned_to column if it doesn't exist
            if (!Schema::hasColumn('project_milestones', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->after('owner_id')->constrained('employees')->nullOnDelete();
            }
        });

        // Copy data from owner_id to assigned_to if owner_id exists
        if (Schema::hasColumn('project_milestones', 'owner_id')) {
            DB::statement('UPDATE project_milestones SET assigned_to = owner_id WHERE owner_id IS NOT NULL AND assigned_to IS NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_milestones', function (Blueprint $table) {
            if (Schema::hasColumn('project_milestones', 'assigned_to')) {
                $table->dropForeign(['assigned_to']);
                $table->dropColumn('assigned_to');
            }
        });
    }
};
