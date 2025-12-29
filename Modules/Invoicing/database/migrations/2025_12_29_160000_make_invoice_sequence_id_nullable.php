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
        // First drop the foreign key constraint
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['invoice_sequence_id']);
        });

        // Make the column nullable
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_sequence_id')->nullable()->change();
        });

        // Re-add the foreign key allowing null values
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('invoice_sequence_id')
                ->references('id')
                ->on('invoice_sequences')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['invoice_sequence_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_sequence_id')->nullable(false)->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('invoice_sequence_id')
                ->references('id')
                ->on('invoice_sequences')
                ->onDelete('restrict');
        });
    }
};
