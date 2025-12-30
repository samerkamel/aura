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
        Schema::create('project_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->enum('type', ['call', 'email', 'meeting', 'message', 'other'])->default('call');
            $table->text('notes');
            $table->string('contact_person')->nullable();
            $table->enum('outcome', ['positive', 'neutral', 'needs_attention', 'escalation'])->default('neutral');
            $table->date('followup_date');
            $table->date('next_followup_date')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'followup_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_followups');
    }
};
