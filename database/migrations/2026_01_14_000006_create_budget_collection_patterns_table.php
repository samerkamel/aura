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
        Schema::create('budget_collection_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_collection_entry_id')->constrained('budget_collection_entries')->cascadeOnDelete();

            $table->string('pattern_name'); // e.g., "Pattern A", "Quarterly", etc.
            $table->decimal('contract_percentage', 5, 2); // % of contracts using this pattern

            // Monthly distribution percentages (12 months)
            $table->decimal('month_1_pct', 5, 2)->default(0);
            $table->decimal('month_2_pct', 5, 2)->default(0);
            $table->decimal('month_3_pct', 5, 2)->default(0);
            $table->decimal('month_4_pct', 5, 2)->default(0);
            $table->decimal('month_5_pct', 5, 2)->default(0);
            $table->decimal('month_6_pct', 5, 2)->default(0);
            $table->decimal('month_7_pct', 5, 2)->default(0);
            $table->decimal('month_8_pct', 5, 2)->default(0);
            $table->decimal('month_9_pct', 5, 2)->default(0);
            $table->decimal('month_10_pct', 5, 2)->default(0);
            $table->decimal('month_11_pct', 5, 2)->default(0);
            $table->decimal('month_12_pct', 5, 2)->default(0);

            $table->timestamps();
            $table->index('budget_collection_entry_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_collection_patterns');
    }
};
