<?php

namespace Modules\Accounting\Services;

use App\Services\ContractRevenueSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Models\Contract;
use Modules\Accounting\Models\ContractPayment;
use Modules\Accounting\Models\Estimate;
use Modules\Project\Models\Project;

/**
 * EstimateConversionService
 *
 * Handles enhanced conversion of estimates to contracts with project linking,
 * allocation settings, and automatic sync to project revenues.
 */
class EstimateConversionService
{
    protected ContractRevenueSyncService $syncService;

    public function __construct(ContractRevenueSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Convert an approved estimate to a contract with project linking.
     *
     * @param Estimate $estimate The estimate to convert
     * @param array $options Conversion options:
     *   - project_action: 'create_new' | 'link_existing' | 'none'
     *   - project_id: int|null (required if project_action is 'link_existing')
     *   - project_name: string|null (optional, for new project)
     *   - project_code: string|null (optional, for new project)
     *   - allocation_type: 'percentage' | 'amount'
     *   - allocation_value: float (percentage 0-100 or amount)
     *   - contract_start_date: string|null (defaults to today)
     *   - sync_to_project: bool (default true)
     */
    public function convertToContractWithProject(Estimate $estimate, array $options = []): array
    {
        if (!$estimate->canBeConverted()) {
            return [
                'success' => false,
                'message' => 'This estimate cannot be converted to a contract.',
            ];
        }

        $options = array_merge([
            'project_action' => 'none',
            'project_id' => null,
            'project_name' => null,
            'project_code' => null,
            'allocation_type' => 'percentage',
            'allocation_value' => 100,
            'contract_start_date' => now()->toDateString(),
            'sync_to_project' => true,
        ], $options);

        return DB::transaction(function () use ($estimate, $options) {
            // 1. Create Contract
            $contract = $this->createContractFromEstimate($estimate, $options['contract_start_date']);

            // 2. Create Contract Payments from Estimate Items
            $this->createPaymentsFromEstimateItems($contract, $estimate);

            // 3. Handle Project Linking
            $project = null;
            if ($options['project_action'] === 'create_new') {
                $project = $this->createNewProject($estimate, $options);
            } elseif ($options['project_action'] === 'link_existing' && $options['project_id']) {
                $project = Project::find($options['project_id']);
            }

            // 4. Link Contract to Project with Allocation
            if ($project) {
                $this->linkContractToProject($contract, $project, $options);

                // 5. Sync to Project Revenues
                if ($options['sync_to_project']) {
                    $this->syncService->syncContractToProjects($contract);
                }
            }

            // 6. Update Estimate with Contract Reference
            $estimate->update([
                'converted_to_contract_id' => $contract->id,
            ]);

            Log::info('Estimate converted to contract', [
                'estimate_id' => $estimate->id,
                'contract_id' => $contract->id,
                'project_id' => $project?->id,
            ]);

            return [
                'success' => true,
                'message' => 'Estimate converted to contract successfully.',
                'contract' => $contract,
                'project' => $project,
            ];
        });
    }

    /**
     * Create a contract from an estimate.
     */
    protected function createContractFromEstimate(Estimate $estimate, ?string $startDate = null): Contract
    {
        $startDate = $startDate ?? now()->toDateString();

        return Contract::create([
            'client_name' => $estimate->client_name,
            'customer_id' => $estimate->customer_id,
            'contract_number' => Contract::generateContractNumber($startDate),
            'description' => $estimate->title . ($estimate->description ? "\n\n" . $estimate->description : ''),
            'total_amount' => $estimate->total,
            'start_date' => $startDate,
            'status' => 'draft',
            'is_active' => true,
            'contact_info' => [
                'email' => $estimate->client_email,
                'address' => $estimate->client_address,
            ],
            'notes' => $estimate->notes,
        ]);
    }

    /**
     * Create contract payments from estimate items.
     */
    protected function createPaymentsFromEstimateItems(Contract $contract, Estimate $estimate): void
    {
        foreach ($estimate->items as $index => $item) {
            ContractPayment::create([
                'contract_id' => $contract->id,
                'name' => $item->description,
                'description' => $item->details,
                'amount' => $item->amount,
                'due_date' => now()->addDays(30 * ($index + 1)),
                'status' => 'pending',
                'is_milestone' => true,
                'sequence_number' => $index + 1,
            ]);
        }
    }

    /**
     * Create a new project from estimate data.
     */
    protected function createNewProject(Estimate $estimate, array $options): Project
    {
        // Generate project code if not provided
        $projectCode = $options['project_code'] ?? $this->generateProjectCode($estimate);
        $projectName = $options['project_name'] ?? $estimate->title;

        return Project::create([
            'name' => $projectName,
            'code' => $projectCode,
            'description' => $estimate->description,
            'customer_id' => $estimate->customer_id,
            'phase' => 'initiation',
            'is_active' => true,
            'start_date' => $options['contract_start_date'] ?? now()->toDateString(),
        ]);
    }

    /**
     * Generate a project code from estimate.
     */
    protected function generateProjectCode(Estimate $estimate): string
    {
        // Generate from first letters of title words, max 6 chars
        $words = preg_split('/\s+/', $estimate->title);
        $code = '';
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $code .= strtoupper(substr($word, 0, 1));
            }
            if (strlen($code) >= 3) {
                break;
            }
        }

        // Add year and sequence
        $year = now()->year;
        $baseCode = $code . $year;

        // Find next available sequence
        $sequence = 1;
        while (Project::where('code', $baseCode . str_pad($sequence, 2, '0', STR_PAD_LEFT))->exists()) {
            $sequence++;
        }

        return $baseCode . str_pad($sequence, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Link a contract to a project with allocation.
     */
    protected function linkContractToProject(Contract $contract, Project $project, array $options): void
    {
        $allocationData = [
            'allocation_type' => $options['allocation_type'],
            'is_primary' => true,
        ];

        if ($options['allocation_type'] === 'percentage') {
            $allocationData['allocation_percentage'] = $options['allocation_value'];
        } else {
            $allocationData['allocation_amount'] = $options['allocation_value'];
        }

        $contract->projects()->attach($project->id, $allocationData);
    }

    /**
     * Simple conversion without project linking (backward compatible).
     */
    public function simpleConvert(Estimate $estimate): Contract
    {
        if (!$estimate->canBeConverted()) {
            throw new \Exception('This estimate cannot be converted to a contract.');
        }

        $result = $this->convertToContractWithProject($estimate, [
            'project_action' => $estimate->project_id ? 'link_existing' : 'none',
            'project_id' => $estimate->project_id,
            'sync_to_project' => (bool) $estimate->project_id,
        ]);

        if (!$result['success']) {
            throw new \Exception($result['message']);
        }

        return $result['contract'];
    }

    /**
     * Get available projects for linking.
     */
    public function getAvailableProjects(): \Illuminate\Database\Eloquent\Collection
    {
        return Project::active()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'customer_id']);
    }

    /**
     * Preview conversion - show what will be created.
     */
    public function previewConversion(Estimate $estimate, array $options = []): array
    {
        $startDate = $options['contract_start_date'] ?? now()->toDateString();

        return [
            'contract_number' => Contract::previewNextContractNumber($startDate),
            'contract_total' => $estimate->total,
            'payments_count' => $estimate->items->count(),
            'payments' => $estimate->items->map(function ($item, $index) {
                return [
                    'name' => $item->description,
                    'amount' => $item->amount,
                    'due_date' => now()->addDays(30 * ($index + 1))->format('Y-m-d'),
                ];
            }),
            'project_code_suggestion' => $this->generateProjectCode($estimate),
        ];
    }
}
