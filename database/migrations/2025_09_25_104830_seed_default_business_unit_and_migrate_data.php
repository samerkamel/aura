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
        DB::transaction(function () {
            // 1. Create default "Head Office" Business Unit
            $headOfficeId = DB::table('business_units')->insertGetId([
                'name' => 'Head Office',
                'code' => 'HQ',
                'description' => 'Main headquarters - manages company-wide operations and expenses',
                'type' => 'head_office',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Migrate existing departments to Head Office BU
            DB::table('departments')
                ->whereNull('business_unit_id')
                ->update([
                    'business_unit_id' => $headOfficeId,
                    'updated_at' => now(),
                ]);

            // 3. Migrate existing contracts to Head Office BU
            // Check if accounting module contracts table exists
            if (Schema::hasTable('contracts')) {
                DB::table('contracts')
                    ->whereNull('business_unit_id')
                    ->update([
                        'business_unit_id' => $headOfficeId,
                        'updated_at' => now(),
                    ]);
            }

            // 4. Migrate existing expense schedules to Head Office BU
            if (Schema::hasTable('expense_schedules')) {
                DB::table('expense_schedules')
                    ->whereNull('business_unit_id')
                    ->update([
                        'business_unit_id' => $headOfficeId,
                        'updated_at' => now(),
                    ]);
            }

            // 5. Assign all existing users to Head Office BU as admins
            $userIds = DB::table('users')->pluck('id');

            foreach ($userIds as $userId) {
                DB::table('business_unit_user')->insert([
                    'user_id' => $userId,
                    'business_unit_id' => $headOfficeId,
                    'role' => 'admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            echo "✅ Default Business Unit created and existing data migrated successfully!\n";
            echo "   - Business Unit ID: {$headOfficeId}\n";
            echo "   - Departments migrated: " . DB::table('departments')->where('business_unit_id', $headOfficeId)->count() . "\n";
            echo "   - Contracts migrated: " . (Schema::hasTable('contracts') ? DB::table('contracts')->where('business_unit_id', $headOfficeId)->count() : 0) . "\n";
            echo "   - Expense schedules migrated: " . (Schema::hasTable('expense_schedules') ? DB::table('expense_schedules')->where('business_unit_id', $headOfficeId)->count() : 0) . "\n";
            echo "   - Users assigned: " . $userIds->count() . "\n";
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function () {
            // Get the Head Office BU ID
            $headOfficeId = DB::table('business_units')
                ->where('code', 'HQ')
                ->where('type', 'head_office')
                ->value('id');

            if ($headOfficeId) {
                // Remove BU assignments from data
                DB::table('departments')
                    ->where('business_unit_id', $headOfficeId)
                    ->update(['business_unit_id' => null]);

                if (Schema::hasTable('contracts')) {
                    DB::table('contracts')
                        ->where('business_unit_id', $headOfficeId)
                        ->update(['business_unit_id' => null]);
                }

                if (Schema::hasTable('expense_schedules')) {
                    DB::table('expense_schedules')
                        ->where('business_unit_id', $headOfficeId)
                        ->update(['business_unit_id' => null]);
                }

                // Remove user assignments
                DB::table('business_unit_user')
                    ->where('business_unit_id', $headOfficeId)
                    ->delete();

                // Delete the Head Office BU
                DB::table('business_units')->where('id', $headOfficeId)->delete();

                echo "✅ Data migration rollback completed!\n";
            }
        });
    }
};
