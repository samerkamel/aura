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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');                           // Full name: "Visitor Management System"
            $table->string('code', 20)->unique();             // Short code: "VIS"
            $table->text('description')->nullable();
            $table->string('jira_project_id')->nullable();    // Jira internal ID
            $table->boolean('needs_monthly_report')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
