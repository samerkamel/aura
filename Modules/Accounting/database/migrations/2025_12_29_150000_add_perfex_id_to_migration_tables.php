<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds perfex_id column to tables that need to track imported Perfex CRM data
     */
    public function up(): void
    {
        // Add perfex_id to customers table
        if (!Schema::hasColumn('customers', 'perfex_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unsignedBigInteger('perfex_id')->nullable()->after('id')->index();
            });
        }

        // Add perfex_id to invoices table
        if (!Schema::hasColumn('invoices', 'perfex_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('perfex_id')->nullable()->after('id')->index();
            });
        }

        // Add perfex_id to expense_schedules table
        if (!Schema::hasColumn('expense_schedules', 'perfex_id')) {
            Schema::table('expense_schedules', function (Blueprint $table) {
                $table->unsignedBigInteger('perfex_id')->nullable()->after('id')->index();
            });
        }

        // Add perfex_id to estimates table
        if (!Schema::hasColumn('estimates', 'perfex_id')) {
            Schema::table('estimates', function (Blueprint $table) {
                $table->unsignedBigInteger('perfex_id')->nullable()->after('id')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('customers', 'perfex_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('perfex_id');
            });
        }

        if (Schema::hasColumn('invoices', 'perfex_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('perfex_id');
            });
        }

        if (Schema::hasColumn('expense_schedules', 'perfex_id')) {
            Schema::table('expense_schedules', function (Blueprint $table) {
                $table->dropColumn('perfex_id');
            });
        }

        if (Schema::hasColumn('estimates', 'perfex_id')) {
            Schema::table('estimates', function (Blueprint $table) {
                $table->dropColumn('perfex_id');
            });
        }
    }
};
