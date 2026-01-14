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
        Schema::create('budget_plans', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique(); // Financial year being budgeted for
            $table->enum('status', ['draft', 'finalized'])->default('draft');
            $table->decimal('opex_global_increase_pct', 5, 2)->default(10.00); // Default 10% increase for OpEx
            $table->decimal('tax_global_increase_pct', 5, 2)->default(10.00); // Default 10% increase for Taxes
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['year', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_plans');
    }
};
