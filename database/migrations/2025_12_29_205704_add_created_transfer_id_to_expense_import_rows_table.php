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
        Schema::table('expense_import_rows', function (Blueprint $table) {
            $table->foreignId('created_transfer_id')->nullable()->after('created_payment_id')->constrained('account_transfers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_import_rows', function (Blueprint $table) {
            $table->dropForeign(['created_transfer_id']);
            $table->dropColumn('created_transfer_id');
        });
    }
};
