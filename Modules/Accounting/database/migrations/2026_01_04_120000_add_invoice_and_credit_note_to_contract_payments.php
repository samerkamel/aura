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
        Schema::table('contract_payments', function (Blueprint $table) {
            // Direct link to invoice for easier tracking
            $table->foreignId('invoice_id')->nullable()->after('contract_id')->constrained('invoices')->nullOnDelete();
            // Link to credit note for payments received without invoice
            $table->foreignId('credit_note_id')->nullable()->after('invoice_id')->constrained('credit_notes')->nullOnDelete();
            // Payment received date (different from paid_date which is when status changed)
            $table->date('payment_received_date')->nullable()->after('paid_date');
            // Payment method and reference
            $table->string('payment_method')->nullable()->after('payment_received_date');
            $table->string('payment_reference')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_payments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['credit_note_id']);
            $table->dropColumn(['invoice_id', 'credit_note_id', 'payment_received_date', 'payment_method', 'payment_reference']);
        });
    }
};
