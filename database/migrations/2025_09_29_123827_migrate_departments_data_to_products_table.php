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
        // Migrate data from departments table to products table
        $departments = DB::table('departments')->get();

        foreach ($departments as $department) {
            DB::table('products')->insert([
                'id' => $department->id, // Keep same IDs to maintain relationships
                'name' => $department->name,
                'code' => $department->code,
                'description' => $department->description,
                'head_of_product' => $department->head_of_department,
                'email' => $department->email,
                'phone' => $department->phone,
                'budget_allocation' => $department->budget_allocation,
                'is_active' => $department->is_active,
                'business_unit_id' => $department->business_unit_id,
                'status' => 'active', // Default status
                'created_by' => 1, // Default user ID
                'updated_by' => 1, // Default user ID
                'created_at' => $department->created_at,
                'updated_at' => $department->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear products table
        DB::table('products')->truncate();
    }
};