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
        Schema::create('project_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_budget_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('cost_type', ['labor', 'expense', 'contractor', 'infrastructure', 'software', 'other']);
            $table->string('description');
            $table->text('notes')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('cost_date');
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('expense_id')->nullable(); // Link to accounting expense
            $table->decimal('hours', 8, 2)->nullable(); // For labor costs
            $table->decimal('hourly_rate', 8, 2)->nullable(); // Rate used for this cost
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_auto_generated')->default(false); // True for worklog-generated costs
            $table->string('reference_type')->nullable(); // 'worklog', 'expense', 'manual'
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of related worklog/expense
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'cost_date']);
            $table->index(['project_id', 'cost_type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_costs');
    }
};
