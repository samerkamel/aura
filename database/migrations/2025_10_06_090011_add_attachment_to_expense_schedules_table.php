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
        Schema::table('expense_schedules', function (Blueprint $table) {
            $table->string('payment_attachment_path')->nullable()->after('payment_notes');
            $table->string('payment_attachment_original_name')->nullable()->after('payment_attachment_path');
            $table->string('payment_attachment_mime_type')->nullable()->after('payment_attachment_original_name');
            $table->integer('payment_attachment_size')->nullable()->after('payment_attachment_mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_schedules', function (Blueprint $table) {
            $table->dropColumn(['payment_attachment_path', 'payment_attachment_original_name', 'payment_attachment_mime_type', 'payment_attachment_size']);
        });
    }
};
