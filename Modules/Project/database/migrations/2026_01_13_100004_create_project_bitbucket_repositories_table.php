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
        // Create pivot table for many-to-many relationship
        Schema::create('project_bitbucket_repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('repo_slug');
            $table->string('repo_name')->nullable();
            $table->string('workspace')->nullable();
            $table->string('repo_uuid')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate links
            $table->unique(['project_id', 'repo_slug'], 'project_repo_unique');

            // Index for faster lookups
            $table->index('repo_slug');
        });

        // Add repository_id to commits table to track which repo the commit came from
        Schema::table('bitbucket_commits', function (Blueprint $table) {
            $table->string('repo_slug')->nullable()->after('project_id');
            $table->index('repo_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bitbucket_commits', function (Blueprint $table) {
            $table->dropIndex(['repo_slug']);
            $table->dropColumn('repo_slug');
        });

        Schema::dropIfExists('project_bitbucket_repositories');
    }
};
