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
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->text('long_description')->nullable()->after('description');
            $table->string('unit', 50)->nullable()->after('unit_price');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('unit');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['long_description', 'unit', 'tax_rate', 'tax_amount']);
        });
    }
};
