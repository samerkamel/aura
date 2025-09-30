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
        Schema::table('business_units', function (Blueprint $table) {
            $table->unsignedBigInteger('sector_id')->default(0)->after('id');
            // Note: We don't add foreign key constraint because sector_id = 0 means "all sectors"
            // which doesn't correspond to an actual sector record
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_units', function (Blueprint $table) {
            $table->dropColumn('sector_id');
        });
    }
};