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
        Schema::create('contract_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->enum('allocation_type', ['percentage', 'amount']);
            $table->decimal('allocation_percentage', 5, 2)->nullable(); // Up to 100.00%
            $table->decimal('allocation_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'department_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_department');
    }
};
