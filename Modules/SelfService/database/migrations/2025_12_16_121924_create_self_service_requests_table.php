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
        Schema::create('self_service_requests', function (Blueprint $table) {
            $table->id();

            // Employee who made the request
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            // Type of request: leave, wfh, permission
            $table->enum('request_type', ['leave', 'wfh', 'permission']);

            // Status flow: pending_manager -> pending_admin -> approved/rejected
            // If no manager, starts at pending_admin
            $table->enum('status', ['pending_manager', 'pending_admin', 'approved', 'rejected', 'cancelled'])
                ->default('pending_manager');

            // Dates for the request
            $table->date('start_date');
            $table->date('end_date')->nullable(); // For single-day requests, end_date = start_date

            // For leave requests - the leave policy type
            $table->foreignId('leave_policy_id')->nullable()->constrained('leave_policies')->nullOnDelete();

            // Request data (JSON) - for storing additional type-specific data
            $table->json('request_data')->nullable();

            // Employee notes when submitting the request
            $table->text('notes')->nullable();

            // Manager who should approve (cached from employee's manager at request time)
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();

            // Manager approval tracking
            $table->timestamp('manager_approved_at')->nullable();
            $table->foreignId('manager_approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Admin approval tracking
            $table->timestamp('admin_approved_at')->nullable();
            $table->foreignId('admin_approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Rejection tracking
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();

            // Cancellation tracking
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index(['employee_id', 'status']);
            $table->index(['manager_id', 'status']);
            $table->index(['request_type', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('self_service_requests');
    }
};
