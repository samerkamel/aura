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
        // Add invoice_id column
        Schema::table('project_costs', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('expense_schedule_id')->constrained('invoices')->nullOnDelete();
            $table->index(['invoice_id']);
        });

        // Modify cost_type enum to include 'tax'
        DB::statement("ALTER TABLE project_costs MODIFY COLUMN cost_type ENUM('labor', 'expense', 'contractor', 'infrastructure', 'software', 'tax', 'other')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_costs', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
        });

        // Revert cost_type enum (remove 'tax')
        DB::statement("ALTER TABLE project_costs MODIFY COLUMN cost_type ENUM('labor', 'expense', 'contractor', 'infrastructure', 'software', 'other')");
    }
};
