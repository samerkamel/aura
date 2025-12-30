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
        Schema::create('project_revenues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->enum('revenue_type', ['contract', 'invoice', 'milestone', 'retainer', 'other']);
            $table->string('description');
            $table->text('notes')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('revenue_date');
            $table->foreignId('contract_id')->nullable(); // Link to contracts
            $table->foreignId('invoice_id')->nullable(); // Link to invoices
            $table->enum('status', ['planned', 'invoiced', 'partial', 'received', 'overdue'])->default('planned');
            $table->decimal('amount_received', 12, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->date('received_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'revenue_date']);
            $table->index(['project_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_revenues');
    }
};
