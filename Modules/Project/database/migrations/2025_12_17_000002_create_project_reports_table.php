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
        Schema::create('project_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');                           // Report title
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_hours', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->json('projects_data')->nullable();        // Snapshot of full report
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_reports');
    }
};
