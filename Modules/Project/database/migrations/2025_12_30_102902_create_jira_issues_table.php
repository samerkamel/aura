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
        Schema::create('jira_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('jira_issue_id')->unique();        // Jira internal ID
            $table->string('issue_key');                      // VIS-123
            $table->string('summary');
            $table->text('description')->nullable();
            $table->string('issue_type');                     // Bug, Story, Task, Epic
            $table->string('status');                         // To Do, In Progress, Done
            $table->string('status_category');                // new, indeterminate, done
            $table->string('priority')->nullable();
            $table->string('assignee_email')->nullable();
            $table->foreignId('assignee_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('reporter_email')->nullable();
            $table->string('parent_key')->nullable();         // For subtasks
            $table->string('epic_key')->nullable();
            $table->integer('story_points')->nullable();
            $table->date('due_date')->nullable();
            $table->json('labels')->nullable();
            $table->json('components')->nullable();
            $table->timestamp('jira_created_at')->nullable();
            $table->timestamp('jira_updated_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status_category']);
            $table->index('issue_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_issues');
    }
};
