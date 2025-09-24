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
        Schema::create('expense_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('expense_categories')->onDelete('restrict');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('frequency_type', ['weekly', 'bi-weekly', 'monthly', 'quarterly', 'yearly']);
            $table->unsignedTinyInteger('frequency_value')->default(1); // e.g., every 2 weeks
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('skip_weekends')->default(false);
            $table->json('excluded_dates')->nullable(); // For holidays or specific dates to skip
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'start_date']);
            $table->index('category_id');
            $table->index(['frequency_type', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_schedules');
    }
};