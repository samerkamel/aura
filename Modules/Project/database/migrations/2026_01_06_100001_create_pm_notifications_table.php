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
        Schema::create('pm_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // followup_due, followup_overdue, milestone_due, milestone_overdue, payment_overdue, health_alert, stale_project
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('reference_type')->nullable(); // ProjectFollowup, ProjectMilestone, ContractPayment, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['type', 'due_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pm_notifications');
    }
};
