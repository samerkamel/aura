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
        Schema::create('jira_settings', function (Blueprint $table) {
            $table->id();
            $table->string('base_url')->nullable();
            $table->string('email')->nullable();
            $table->text('api_token')->nullable(); // Will be encrypted
            $table->text('billable_projects')->nullable(); // Comma-separated, empty = all projects
            $table->boolean('sync_enabled')->default(false);
            $table->string('sync_frequency')->default('daily'); // daily, weekly, monthly
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_settings');
    }
};
