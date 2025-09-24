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
        Schema::table('expense_schedules', function (Blueprint $table) {
            // Add payment tracking fields
            $table->enum('payment_status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->foreignId('paid_from_account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->date('paid_date')->nullable();
            $table->decimal('paid_amount', 10, 2)->nullable();
            $table->text('payment_notes')->nullable();

            // Add expense type for one-time vs recurring
            $table->enum('expense_type', ['recurring', 'one_time'])->default('recurring');
            $table->date('expense_date')->nullable(); // For one-time expenses

            $table->index(['payment_status', 'expense_date']);
            $table->index(['expense_type', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paid_from_account_id');
            $table->dropColumn([
                'payment_status',
                'paid_date',
                'paid_amount',
                'payment_notes',
                'expense_type',
                'expense_date'
            ]);
        });
    }
};