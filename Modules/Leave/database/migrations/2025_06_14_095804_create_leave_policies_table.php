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
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Standard PTO", "Sick Leave"
            $table->enum('type', ['pto', 'sick_leave', 'emergency']); // Policy type
            $table->text('description')->nullable(); // Policy description
            $table->integer('initial_days')->nullable(); // Initial days granted (for PTO)
            $table->json('config')->nullable(); // For flexible configuration (sick leave specific settings)
            $table->boolean('is_active')->default(true); // Whether policy is currently active
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};
