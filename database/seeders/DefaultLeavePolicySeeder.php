<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Leave\Models\LeavePolicy;
use Modules\Leave\Models\LeavePolicyTier;

/**
 * Default Leave Policy Seeder
 *
 * Creates default PTO and sick leave policies for the application.
 * This seeder ensures that leave management functionality works
 * out of the box with standard company policies.
 *
 * @author Dev Agent
 */
class DefaultLeavePolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createPtoPolicy();
        $this->createSickLeavePolicy();

        $this->command->info('Default leave policies created successfully.');
    }

    /**
     * Create default PTO policy with service-based tiers.
     */
    private function createPtoPolicy(): void
    {
        // Create PTO policy
        $ptoPolicy = LeavePolicy::create([
            'name' => 'Standard PTO Policy',
            'type' => 'pto',
            'description' => 'Company-wide PTO policy with service-based accrual tiers',
            'initial_days' => 6,
            'is_active' => true,
        ]);

        // Create PTO tiers based on years of service
        LeavePolicyTier::create([
            'leave_policy_id' => $ptoPolicy->id,
            'min_years' => 0,
            'max_years' => 2,
            'annual_days' => 15,
        ]);

        LeavePolicyTier::create([
            'leave_policy_id' => $ptoPolicy->id,
            'min_years' => 3,
            'max_years' => 5,
            'annual_days' => 20,
        ]);

        LeavePolicyTier::create([
            'leave_policy_id' => $ptoPolicy->id,
            'min_years' => 6,
            'max_years' => null, // 6+ years, no upper limit
            'annual_days' => 25,
        ]);

        $this->command->info('Created PTO policy with 3 service tiers');
    }

    /**
     * Create default sick leave policy.
     */
    private function createSickLeavePolicy(): void
    {
        $sickLeavePolicy = LeavePolicy::create([
            'name' => 'Standard Sick Leave Policy',
            'type' => 'sick_leave',
            'description' => 'Company-wide sick leave policy',
            'config' => [
                'days' => 60,
                'period_in_years' => 3,
            ],
            'is_active' => true,
        ]);

        $this->command->info('Created sick leave policy (60 days every 3 years)');
    }
}
