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
    Schema::create('salary_histories', function (Blueprint $table) {
      $table->id();
      $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
      $table->decimal('old_salary', 10, 2);
      $table->decimal('new_salary', 10, 2);
      $table->timestamp('change_date')->useCurrent();
      $table->text('reason')->nullable();
      $table->timestamps();

      $table->index(['employee_id', 'change_date']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('salary_histories');
  }
};
