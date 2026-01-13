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
        Schema::create('bitbucket_commits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('commit_hash', 40)->unique();       // Full SHA hash
            $table->string('short_hash', 12);                  // Short hash for display
            $table->text('message');                           // Commit message
            $table->string('author_name');                     // Author display name
            $table->string('author_email')->nullable();        // Author email
            $table->string('author_username')->nullable();     // Bitbucket username
            $table->timestamp('committed_at');                 // When the commit was made
            $table->string('branch')->nullable();              // Branch name if available
            $table->integer('additions')->default(0);          // Lines added
            $table->integer('deletions')->default(0);          // Lines deleted
            $table->json('files_changed')->nullable();         // List of changed files
            $table->string('bitbucket_url')->nullable();       // Link to commit on Bitbucket
            $table->timestamps();

            // Indexes for common queries
            $table->index(['project_id', 'committed_at']);
            $table->index('author_email');
            $table->index('committed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitbucket_commits');
    }
};
