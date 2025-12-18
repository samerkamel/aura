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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('national_id', 50)->nullable()->after('attendance_id');
            $table->string('national_insurance_number', 50)->nullable()->after('national_id');
            $table->json('emergency_contact')->nullable()->after('contact_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'national_id', 'national_insurance_number', 'emergency_contact']);
        });
    }
};
