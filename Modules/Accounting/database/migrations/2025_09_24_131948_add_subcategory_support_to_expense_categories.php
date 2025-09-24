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
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('expense_categories')->onDelete('cascade');
            $table->integer('sort_order')->default(0);

            $table->index(['parent_id', 'sort_order']);
        });

        // Add subcategory_id to expense_schedules table
        Schema::table('expense_schedules', function (Blueprint $table) {
            $table->foreignId('subcategory_id')->nullable()->constrained('expense_categories')->onDelete('set null');

            $table->index('subcategory_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subcategory_id');
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn('sort_order');
        });
    }
};