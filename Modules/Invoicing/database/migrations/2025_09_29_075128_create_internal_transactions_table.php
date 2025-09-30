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
        Schema::create('internal_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->date('transaction_date');
            $table->decimal('total_amount', 15, 2);
            $table->enum('status', ['draft', 'pending', 'approved', 'cancelled'])->default('draft');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');

            // Business units involved
            $table->unsignedBigInteger('from_business_unit_id');
            $table->unsignedBigInteger('to_business_unit_id');

            // Transaction details
            $table->text('description');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            // User tracking
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Sequence management
            $table->unsignedBigInteger('internal_sequence_id')->nullable();

            $table->timestamps();

            $table->foreign('from_business_unit_id')->references('id')->on('business_units')->onDelete('restrict');
            $table->foreign('to_business_unit_id')->references('id')->on('business_units')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['status']);
            $table->index(['approval_status']);
            $table->index(['transaction_date']);
            $table->index(['from_business_unit_id']);
            $table->index(['to_business_unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_transactions');
    }
};