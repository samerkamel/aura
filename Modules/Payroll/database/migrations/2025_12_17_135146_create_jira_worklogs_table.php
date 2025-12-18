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
        Schema::create('jira_worklogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('jira_author_name');
            $table->string('issue_key');
            $table->string('issue_summary');
            $table->datetime('worklog_started');
            $table->datetime('worklog_created');
            $table->string('timezone')->nullable();
            $table->decimal('time_spent_hours', 8, 2);
            $table->text('comment')->nullable();
            $table->foreignId('sync_log_id')->nullable()->constrained('jira_sync_logs')->nullOnDelete();
            $table->timestamps();

            // Unique constraint to prevent duplicates
            $table->unique(['employee_id', 'issue_key', 'worklog_started'], 'jira_worklog_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_worklogs');
    }
};
