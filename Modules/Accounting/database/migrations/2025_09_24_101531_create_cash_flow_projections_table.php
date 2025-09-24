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
        Schema::create('cash_flow_projections', function (Blueprint $table) {
            $table->id();
            $table->date('projection_date');
            $table->decimal('projected_income', 12, 2)->default(0);
            $table->decimal('projected_expenses', 12, 2)->default(0);
            $table->decimal('net_flow', 12, 2)->default(0); // income - expenses
            $table->decimal('running_balance', 12, 2)->default(0); // cumulative balance
            $table->enum('period_type', ['daily', 'weekly', 'monthly']);
            $table->boolean('has_deficit')->default(false); // for quick deficit identification
            $table->json('income_breakdown')->nullable(); // detailed breakdown by contract
            $table->json('expense_breakdown')->nullable(); // detailed breakdown by category
            $table->timestamp('calculated_at');
            $table->timestamps();

            // Indexes
            $table->unique(['projection_date', 'period_type']); // One projection per date per period type
            $table->index('has_deficit');
            $table->index(['period_type', 'projection_date']);
            $table->index('calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_flow_projections');
    }
};