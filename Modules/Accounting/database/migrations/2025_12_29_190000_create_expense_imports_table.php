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
        Schema::create('expense_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->enum('status', ['parsing', 'reviewing', 'previewing', 'executing', 'completed', 'failed'])->default('parsing');
            $table->integer('total_rows')->default(0);
            $table->integer('valid_rows')->default(0);
            $table->integer('warning_rows')->default(0);
            $table->integer('error_rows')->default(0);
            $table->integer('imported_rows')->default(0);
            $table->json('column_mappings')->nullable(); // Store detected column mappings
            $table->json('summary')->nullable(); // Store import summary after completion
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_import_id')->constrained()->onDelete('cascade');
            $table->integer('row_number');
            $table->json('raw_data'); // Original parsed data from CSV

            // Parsed fields
            $table->date('expense_date')->nullable();
            $table->integer('year')->nullable();
            $table->integer('month')->nullable();
            $table->string('item_description')->nullable();

            // Type mapping
            $table->string('expense_type_raw')->nullable(); // Original value from CSV
            $table->foreignId('expense_type_id')->nullable()->constrained('expense_types')->nullOnDelete();

            // Category mapping
            $table->string('category_raw')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();

            // Subcategory mapping
            $table->string('subcategory_raw')->nullable();
            $table->foreignId('subcategory_id')->nullable()->constrained('expense_categories')->nullOnDelete();

            // Customer mapping
            $table->string('customer_raw')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->boolean('create_customer')->default(false); // Flag to create new customer

            // Department/Invoice number from CSV
            $table->string('department_number')->nullable();

            // Account amounts - JSON object {account_id: amount}
            $table->json('account_amounts')->nullable();

            // Totals
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('absolute_total', 15, 2)->default(0);

            // Comment
            $table->text('comment')->nullable();

            // Income/Invoice linking
            $table->boolean('is_income')->default(false);
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->boolean('income_without_invoice')->default(false);

            // Row status
            $table->enum('status', ['pending', 'valid', 'warning', 'error', 'skipped', 'imported'])->default('pending');
            $table->json('validation_messages')->nullable(); // Warnings and errors

            // What action to take
            $table->enum('action', ['create_expense', 'create_income', 'link_invoice', 'skip', 'balance_swap'])->nullable();

            // Result after import
            $table->foreignId('created_expense_id')->nullable();
            $table->foreignId('created_payment_id')->nullable();

            $table->timestamps();

            $table->index(['expense_import_id', 'status']);
            $table->index(['expense_import_id', 'row_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_import_rows');
        Schema::dropIfExists('expense_imports');
    }
};
