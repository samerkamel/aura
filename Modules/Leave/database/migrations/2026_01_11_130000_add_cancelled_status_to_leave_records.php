<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter the status ENUM to include 'cancelled'
        DB::statement("ALTER TABLE leave_records MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'approved'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original ENUM (only if no 'cancelled' records exist)
        DB::statement("ALTER TABLE leave_records MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved'");
    }
};
