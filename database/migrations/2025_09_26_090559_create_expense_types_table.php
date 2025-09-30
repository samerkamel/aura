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
        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // CapEx, OpEx, CoS, etc.
            $table->string('code')->unique(); // CAPEX, OPEX, COS, etc.
            $table->string('description')->nullable();
            $table->string('color')->default('#007bff'); // Display color for UI
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Insert default expense types
        DB::table('expense_types')->insert([
            [
                'name' => 'Capital Expenses',
                'code' => 'CapEx',
                'description' => 'Capital expenditures for long-term assets and investments',
                'color' => '#28a745',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Operational Expenses',
                'code' => 'OpEx',
                'description' => 'Operating expenses for day-to-day business operations',
                'color' => '#dc3545',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cost of Sales',
                'code' => 'CoS',
                'description' => 'Direct costs attributable to the production of goods sold',
                'color' => '#fd7e14',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Administrative Expenses',
                'code' => 'Admin',
                'description' => 'General administrative and overhead expenses',
                'color' => '#6f42c1',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_types');
    }
};