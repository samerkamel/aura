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
            $table->date('last_followup_date')->nullable()->after('is_active');
            $table->date('next_followup_date')->nullable()->after('last_followup_date');
            $table->enum('followup_status', ['up_to_date', 'due_soon', 'overdue', 'none'])->default('none')->after('next_followup_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['last_followup_date', 'next_followup_date', 'followup_status']);
        });
    }
};
