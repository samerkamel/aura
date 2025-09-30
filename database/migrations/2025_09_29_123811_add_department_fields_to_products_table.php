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
        Schema::table('products', function (Blueprint $table) {
            // Add department-specific columns that are now product columns
            $table->string('head_of_product')->nullable()->after('description');
            $table->string('email')->nullable()->after('head_of_product');
            $table->string('phone', 20)->nullable()->after('email');
            $table->decimal('budget_allocation', 15, 2)->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('budget_allocation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Restore old product columns
            $table->decimal('price', 10, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('status')->default('active');

            // Remove department columns
            $table->dropColumn(['head_of_product', 'email', 'phone', 'budget_allocation', 'is_active']);
        });
    }
};
