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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->enum('status', ['draft', 'sent', 'paid', 'cancelled', 'overdue'])->default('draft');

            // Relations
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('business_unit_id');
            $table->unsignedBigInteger('invoice_sequence_id');
            $table->unsignedBigInteger('created_by');

            // Invoice details
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->string('reference')->nullable();

            // Payment tracking
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('paid_date')->nullable();
            $table->text('payment_notes')->nullable();

            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('business_unit_id')->references('id')->on('business_units')->onDelete('cascade');
            $table->foreign('invoice_sequence_id')->references('id')->on('invoice_sequences')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            $table->index(['status']);
            $table->index(['invoice_date']);
            $table->index(['due_date']);
            $table->index(['customer_id']);
            $table->index(['business_unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};