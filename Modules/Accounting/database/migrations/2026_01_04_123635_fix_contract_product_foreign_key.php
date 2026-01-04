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

        // Drop the old foreign key that references departments using raw SQL
        DB::statement('ALTER TABLE `contract_product` DROP FOREIGN KEY `contract_department_department_id_foreign`');

        // Add the correct foreign key that references products
        DB::statement('ALTER TABLE `contract_product` ADD CONSTRAINT `contract_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `contract_product` DROP FOREIGN KEY `contract_product_product_id_foreign`');
        DB::statement('ALTER TABLE `contract_product` ADD CONSTRAINT `contract_department_department_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE');
    }
};
