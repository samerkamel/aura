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
        Schema::create('permission_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('date');
            $table->integer('minutes_used');
            $table->unsignedBigInteger('granted_by_user_id');
            $table->text('reason')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('granted_by_user_id')->references('id')->on('users')->onDelete('cascade');

            // Unique constraint - only one permission usage per employee per date
            $table->unique(['employee_id', 'date'], 'permission_usages_employee_date_unique');

            // Indexes for better query performance
            $table->index('date');
            $table->index('granted_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_usages');
    }
};
