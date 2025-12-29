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
        Schema::table('company_settings', function (Blueprint $table) {
            // Fiscal/Payroll cycle settings
            // cycle_start_day: The day of the month when the cycle starts (1-28)
            // e.g., 26 means cycle runs from 26th to 25th of next month
            $table->unsignedTinyInteger('cycle_start_day')->default(1)->after('currency');

            // Fiscal year start month (1-12)
            // Combined with cycle_start_day determines fiscal year
            // e.g., month=12, day=26 means fiscal year is Dec 26 to Dec 25
            $table->unsignedTinyInteger('fiscal_year_start_month')->default(1)->after('cycle_start_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['cycle_start_day', 'fiscal_year_start_month']);
        });
    }
};
