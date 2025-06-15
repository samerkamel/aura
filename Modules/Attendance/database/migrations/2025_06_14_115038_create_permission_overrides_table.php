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
        Schema::create('permission_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('payroll_period_start_date');
            $table->integer('extra_permissions_granted');
            $table->unsignedBigInteger('granted_by_user_id');
            $table->text('reason')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('granted_by_user_id')->references('id')->on('users')->onDelete('cascade');

            // Unique constraint to prevent duplicate overrides for same employee/period
            $table->unique(['employee_id', 'payroll_period_start_date'], 'permission_overrides_unique');

            // Indexes for better performance
            $table->index('payroll_period_start_date');
            $table->index('granted_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_overrides');
    }
};
