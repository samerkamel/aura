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
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('expense_type_id')->nullable()->after('parent_id');
            $table->foreign('expense_type_id')->references('id')->on('expense_types')->onDelete('set null');
        });

        // Set default expense type for existing main categories
        // Only main categories (parent_id = null) should have expense types
        $opexTypeId = DB::table('expense_types')->where('code', 'OpEx')->value('id');

        DB::table('expense_categories')
            ->whereNull('parent_id') // Only main categories
            ->whereNull('expense_type_id') // Only if not already set
            ->update(['expense_type_id' => $opexTypeId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropForeign(['expense_type_id']);
            $table->dropColumn('expense_type_id');
        });
    }
};