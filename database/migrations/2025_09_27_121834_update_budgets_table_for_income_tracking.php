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
        Schema::table('budgets', function (Blueprint $table) {
            // Rename columns to reflect income tracking
            $table->renameColumn('budget_amount', 'target_revenue');
            $table->renameColumn('allocated_amount', 'projected_revenue');
            $table->renameColumn('spent_amount', 'actual_revenue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            // Rename columns back to original names
            $table->renameColumn('target_revenue', 'budget_amount');
            $table->renameColumn('projected_revenue', 'allocated_amount');
            $table->renameColumn('actual_revenue', 'spent_amount');
        });
    }
};