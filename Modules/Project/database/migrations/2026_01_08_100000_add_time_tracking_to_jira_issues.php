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
        Schema::table('jira_issues', function (Blueprint $table) {
            $table->bigInteger('original_estimate_seconds')->nullable()->after('story_points');
            $table->bigInteger('remaining_estimate_seconds')->nullable()->after('original_estimate_seconds');
            $table->bigInteger('time_spent_seconds')->nullable()->after('remaining_estimate_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jira_issues', function (Blueprint $table) {
            $table->dropColumn(['original_estimate_seconds', 'remaining_estimate_seconds', 'time_spent_seconds']);
        });
    }
};
