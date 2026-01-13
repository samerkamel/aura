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
        Schema::create('bitbucket_settings', function (Blueprint $table) {
            $table->id();
            $table->string('workspace')->nullable();           // Bitbucket workspace slug
            $table->string('email')->nullable();               // Atlassian account email
            $table->text('api_token')->nullable();             // Encrypted API token
            $table->boolean('sync_enabled')->default(false);   // Enable automatic sync
            $table->string('sync_frequency')->default('daily'); // daily, hourly, manual
            $table->timestamp('last_sync_at')->nullable();     // Last successful sync
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitbucket_settings');
    }
};
