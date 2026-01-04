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
        // First, clear any existing data that might have invalid references
        DB::table('contract_product')->truncate();

        // Drop the old foreign key that references departments
        Schema::table('contract_product', function (Blueprint $table) {
            // Drop the foreign key constraint (try both possible names)
            try {
                $table->dropForeign(['product_id']);
            } catch (\Exception $e) {
                // Try alternative constraint name
                try {
                    $table->dropForeign('contract_department_department_id_foreign');
                } catch (\Exception $e2) {
                    // Constraint might not exist
                }
            }
        });

        // Add the correct foreign key that references products
        Schema::table('contract_product', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_product', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('contract_product', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')
                ->on('departments')
                ->onDelete('cascade');
        });
    }
};
