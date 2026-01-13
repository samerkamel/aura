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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('bitbucket_workspace')->nullable()->after('jira_project_id');
            $table->string('bitbucket_repo_slug')->nullable()->after('bitbucket_workspace');
            $table->string('bitbucket_repo_uuid')->nullable()->after('bitbucket_repo_slug');
            $table->timestamp('bitbucket_last_sync_at')->nullable()->after('bitbucket_repo_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'bitbucket_workspace',
                'bitbucket_repo_slug',
                'bitbucket_repo_uuid',
                'bitbucket_last_sync_at',
            ]);
        });
    }
};
