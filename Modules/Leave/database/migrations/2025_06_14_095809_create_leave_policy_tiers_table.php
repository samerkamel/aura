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
        Schema::create('leave_policy_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_policy_id')->constrained('leave_policies')->onDelete('cascade');
            $table->integer('min_years'); // Minimum years of service for this tier
            $table->integer('max_years')->nullable(); // Maximum years of service (null = unlimited)
            $table->integer('annual_days'); // Annual days granted for this tier
            $table->decimal('monthly_accrual_rate', 8, 2)->nullable(); // Calculated monthly accrual rate
            $table->timestamps();

            $table->index(['leave_policy_id', 'min_years']);
            $table->index('min_years');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_policy_tiers');
    }
};
