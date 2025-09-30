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
        // Rename the table from contract_department to contract_product
        Schema::rename('contract_department', 'contract_product');

        // Rename the column from department_id to product_id
        Schema::table('contract_product', function (Blueprint $table) {
            $table->renameColumn('department_id', 'product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename column back
        Schema::table('contract_product', function (Blueprint $table) {
            $table->renameColumn('product_id', 'department_id');
        });

        // Rename table back
        Schema::rename('contract_product', 'contract_department');
    }
};