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
        Schema::create('budget_capacity_hires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_capacity_entry_id')->constrained('budget_capacity_entries')->cascadeOnDelete();
            $table->integer('hire_month')->between(1, 12); // Month number (1-12)
            $table->integer('hire_count')->default(1); // Number of new hires in this month
            $table->timestamps();

            $table->index('budget_capacity_entry_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_capacity_hires');
    }
};
