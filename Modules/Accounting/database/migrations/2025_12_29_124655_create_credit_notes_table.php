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
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('credit_note_number')->unique();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();

            // Client info (fallback if no customer)
            $table->string('client_name')->nullable();
            $table->string('client_email')->nullable();
            $table->text('client_address')->nullable();

            // Credit note details
            $table->date('credit_note_date');
            $table->string('reference')->nullable();
            $table->enum('status', ['draft', 'open', 'closed', 'void'])->default('draft');

            // Amounts
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(14.00);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('applied_amount', 12, 2)->default(0);
            $table->decimal('remaining_credits', 12, 2)->default(0);

            // Notes
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('terms')->nullable();

            // Tracking
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('sent_at')->nullable();

            // Legacy mapping for Perfex migration
            $table->unsignedBigInteger('perfex_id')->nullable()->index();

            $table->timestamps();

            $table->index(['status', 'credit_note_date']);
            $table->index('customer_id');
        });

        // Credit note items table
        Schema::create('credit_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_note_id')->constrained()->onDelete('cascade');
            $table->string('description');
            $table->text('details')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->default('unit');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('credit_note_id');
        });

        // Credit applications table - tracks which invoices credits were applied to
        Schema::create('credit_note_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_note_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->decimal('amount_applied', 12, 2);
            $table->date('applied_date');
            $table->text('notes')->nullable();
            $table->foreignId('applied_by')->constrained('users');
            $table->timestamps();

            $table->index(['credit_note_id', 'invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_note_applications');
        Schema::dropIfExists('credit_note_items');
        Schema::dropIfExists('credit_notes');
    }
};
