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
        Schema::table('project_employee', function (Blueprint $table) {
            $table->decimal('allocation_percentage', 5, 2)->default(100)->after('role');
            $table->date('start_date')->nullable()->after('allocation_percentage');
            $table->date('end_date')->nullable()->after('start_date');
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('end_date');
            $table->text('notes')->nullable()->after('hourly_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_employee', function (Blueprint $table) {
            $table->dropColumn(['allocation_percentage', 'start_date', 'end_date', 'hourly_rate', 'notes']);
        });
    }
};
