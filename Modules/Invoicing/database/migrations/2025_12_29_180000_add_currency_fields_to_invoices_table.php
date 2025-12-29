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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('currency', 3)->default('EGP')->after('total_amount');
            $table->decimal('exchange_rate', 15, 6)->default(1)->after('currency');
            // Store amounts in original currency
            $table->decimal('subtotal_in_base', 15, 2)->nullable()->after('exchange_rate');
            $table->decimal('total_in_base', 15, 2)->nullable()->after('subtotal_in_base');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['currency', 'exchange_rate', 'subtotal_in_base', 'total_in_base']);
        });
    }
};
