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
        Schema::table('contract_project', function (Blueprint $table) {
            $table->enum('allocation_type', ['percentage', 'amount'])->default('percentage')->after('project_id');
            $table->decimal('allocation_percentage', 5, 2)->nullable()->after('allocation_type'); // e.g., 50.00 = 50%
            $table->decimal('allocation_amount', 12, 2)->nullable()->after('allocation_percentage'); // Fixed amount
            $table->boolean('is_primary')->default(false)->after('allocation_amount'); // Primary project flag
            $table->text('notes')->nullable()->after('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_project', function (Blueprint $table) {
            $table->dropColumn(['allocation_type', 'allocation_percentage', 'allocation_amount', 'is_primary', 'notes']);
        });
    }
};
