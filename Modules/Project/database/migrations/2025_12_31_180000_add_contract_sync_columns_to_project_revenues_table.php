<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds contract payment sync tracking to project revenues
     * and sync status tracking to contract payments for bidirectional sync.
     */
    public function up(): void
    {
        // Add contract payment link to project_revenues
        Schema::table('project_revenues', function (Blueprint $table) {
            $table->foreignId('contract_payment_id')->nullable()->after('contract_id')
                ->constrained('contract_payments')->nullOnDelete();
            $table->boolean('synced_from_contract')->default(false)->after('created_by');
            $table->timestamp('synced_at')->nullable()->after('synced_from_contract');

            $table->index(['contract_id', 'contract_payment_id']);
        });

        // Add sync tracking to contract_payments
        Schema::table('contract_payments', function (Blueprint $table) {
            $table->foreignId('project_revenue_id')->nullable()->after('sequence_number')
                ->constrained('project_revenues')->nullOnDelete();
            $table->boolean('synced_to_project')->default(false)->after('project_revenue_id');
            $table->timestamp('synced_to_project_at')->nullable()->after('synced_to_project');

            $table->index(['contract_id', 'synced_to_project']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_payments', function (Blueprint $table) {
            $table->dropIndex(['contract_id', 'synced_to_project']);
            $table->dropForeign(['project_revenue_id']);
            $table->dropColumn([
                'project_revenue_id',
                'synced_to_project',
                'synced_to_project_at',
            ]);
        });

        Schema::table('project_revenues', function (Blueprint $table) {
            $table->dropIndex(['contract_id', 'contract_payment_id']);
            $table->dropForeign(['contract_payment_id']);
            $table->dropColumn([
                'contract_payment_id',
                'synced_from_contract',
                'synced_at',
            ]);
        });
    }
};
