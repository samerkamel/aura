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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('position')->nullable();
            $table->date('start_date')->nullable();
            $table->json('contact_info')->nullable(); // Store phone, address etc.
            $table->text('bank_info')->nullable(); // Store bank name, account number, etc. (encrypted)
            $table->decimal('base_salary', 10, 2)->nullable();
            $table->enum('status', ['active', 'terminated', 'resigned'])->default('active');
            $table->date('termination_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
