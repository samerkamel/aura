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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->string('contract_number')->unique();
            $table->text('description')->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->boolean('is_active')->default(true);
            $table->json('contact_info')->nullable(); // Client contact information
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'status']);
            $table->index('start_date');
            $table->index('client_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};