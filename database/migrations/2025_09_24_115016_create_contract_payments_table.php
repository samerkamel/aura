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
        Schema::create('contract_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->date('paid_date')->nullable();
            $table->decimal('paid_amount', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_milestone')->default(true); // true for milestones, false for recurring
            $table->integer('sequence_number')->default(0); // for ordering payments
            $table->timestamps();

            // Indexes
            $table->index(['contract_id', 'due_date']);
            $table->index(['status', 'due_date']);
            $table->index(['contract_id', 'sequence_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_payments');
    }
};