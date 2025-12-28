<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\Estimate;
use Modules\Accounting\Models\EstimateItem;
use Modules\Accounting\Models\Contract;
use Modules\Accounting\Models\ContractPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * EstimateService
 *
 * Handles business logic for estimates including creation, status updates,
 * and conversion to contracts.
 */
class EstimateService
{
    /**
     * Create a new estimate with items.
     */
    public function createEstimate(array $data, array $items): Estimate
    {
        return DB::transaction(function () use ($data, $items) {
            // Generate estimate number
            $data['estimate_number'] = Estimate::generateNumber();
            $data['created_by'] = Auth::id();

            // Create estimate
            $estimate = Estimate::create($data);

            // Create items
            $this->syncItems($estimate, $items);

            return $estimate->fresh(['items']);
        });
    }

    /**
     * Update an existing estimate with items.
     */
    public function updateEstimate(Estimate $estimate, array $data, array $items): Estimate
    {
        if (!$estimate->canBeEdited()) {
            throw new \Exception('This estimate cannot be edited.');
        }

        return DB::transaction(function () use ($estimate, $data, $items) {
            $estimate->update($data);

            // Sync items
            $this->syncItems($estimate, $items);

            return $estimate->fresh(['items']);
        });
    }

    /**
     * Sync estimate items.
     */
    protected function syncItems(Estimate $estimate, array $items): void
    {
        // Delete existing items
        $estimate->items()->delete();

        // Create new items
        foreach ($items as $index => $itemData) {
            $itemData['estimate_id'] = $estimate->id;
            $itemData['sort_order'] = $index;
            EstimateItem::create($itemData);
        }
    }

    /**
     * Mark estimate as sent.
     */
    public function markAsSent(Estimate $estimate): Estimate
    {
        if (!$estimate->canBeSent()) {
            throw new \Exception('This estimate cannot be sent.');
        }

        $estimate->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return $estimate->fresh();
    }

    /**
     * Mark estimate as approved.
     */
    public function markAsApproved(Estimate $estimate): Estimate
    {
        if ($estimate->status !== 'sent') {
            throw new \Exception('Only sent estimates can be approved.');
        }

        $estimate->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return $estimate->fresh();
    }

    /**
     * Mark estimate as rejected.
     */
    public function markAsRejected(Estimate $estimate): Estimate
    {
        if ($estimate->status !== 'sent') {
            throw new \Exception('Only sent estimates can be rejected.');
        }

        $estimate->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);

        return $estimate->fresh();
    }

    /**
     * Convert approved estimate to contract.
     */
    public function convertToContract(Estimate $estimate): Contract
    {
        if (!$estimate->canBeConverted()) {
            throw new \Exception('This estimate cannot be converted to a contract.');
        }

        return DB::transaction(function () use ($estimate) {
            // Generate contract number
            $year = now()->year;
            $lastContract = Contract::whereYear('created_at', $year)
                ->orderByDesc('id')
                ->first();
            $sequence = $lastContract
                ? (int) substr($lastContract->contract_number, -4) + 1
                : 1;
            $contractNumber = sprintf('CON-%d-%04d', $year, $sequence);

            // Create contract
            $contract = Contract::create([
                'client_name' => $estimate->client_name,
                'customer_id' => $estimate->customer_id,
                'contract_number' => $contractNumber,
                'description' => $estimate->title . ($estimate->description ? "\n\n" . $estimate->description : ''),
                'total_amount' => $estimate->total,
                'start_date' => now(),
                'status' => 'draft',
                'is_active' => true,
                'contact_info' => [
                    'email' => $estimate->client_email,
                    'address' => $estimate->client_address,
                ],
                'notes' => $estimate->notes,
            ]);

            // Create payment milestones from estimate items
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

            // Link project if estimate had one
            if ($estimate->project_id) {
                $contract->projects()->attach($estimate->project_id);
            }

            // Update estimate with contract reference
            $estimate->update([
                'converted_to_contract_id' => $contract->id,
            ]);

            return $contract;
        });
    }

    /**
     * Duplicate an estimate as a new draft.
     */
    public function duplicateEstimate(Estimate $estimate): Estimate
    {
        return DB::transaction(function () use ($estimate) {
            // Create new estimate
            $newEstimate = Estimate::create([
                'estimate_number' => Estimate::generateNumber(),
                'customer_id' => $estimate->customer_id,
                'project_id' => $estimate->project_id,
                'client_name' => $estimate->client_name,
                'client_email' => $estimate->client_email,
                'client_address' => $estimate->client_address,
                'title' => $estimate->title . ' (Copy)',
                'description' => $estimate->description,
                'issue_date' => now(),
                'valid_until' => $estimate->valid_until ? now()->addDays($estimate->issue_date->diffInDays($estimate->valid_until)) : null,
                'status' => 'draft',
                'subtotal' => $estimate->subtotal,
                'vat_rate' => $estimate->vat_rate,
                'vat_amount' => $estimate->vat_amount,
                'total' => $estimate->total,
                'notes' => $estimate->notes,
                'internal_notes' => $estimate->internal_notes,
                'created_by' => Auth::id(),
            ]);

            // Duplicate items
            foreach ($estimate->items as $item) {
                EstimateItem::create([
                    'estimate_id' => $newEstimate->id,
                    'description' => $item->description,
                    'details' => $item->details,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'amount' => $item->amount,
                    'sort_order' => $item->sort_order,
                ]);
            }

            return $newEstimate->fresh(['items']);
        });
    }

    /**
     * Delete an estimate (only drafts).
     */
    public function deleteEstimate(Estimate $estimate): bool
    {
        if ($estimate->status !== 'draft') {
            throw new \Exception('Only draft estimates can be deleted.');
        }

        return $estimate->delete();
    }
}
