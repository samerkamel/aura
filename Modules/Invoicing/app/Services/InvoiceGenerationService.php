<?php

namespace Modules\Invoicing\Services;

use Modules\Invoicing\Models\Invoice;
use Modules\Invoicing\Models\InvoiceSequence;
use Modules\Accounting\Models\ContractPayment;
use Modules\Accounting\Models\Contract;
use App\Models\Customer;
use Carbon\Carbon;

/**
 * InvoiceGenerationService
 *
 * Handles automatic invoice generation from contract payments and schedules.
 */
class InvoiceGenerationService
{
    /**
     * Generate invoice from a specific contract payment.
     */
    public function generateFromContractPayment(ContractPayment $contractPayment, array $options = []): Invoice
    {
        $contract = $contractPayment->contract;

        // Find appropriate sequence
        $sequence = $this->findSequenceForContract($contract);
        if (!$sequence) {
            throw new \Exception('No suitable invoice sequence found for this contract.');
        }

        // Use draft placeholder - real number assigned when invoice is sent
        $invoiceNumber = Invoice::generateDraftNumber();

        // Calculate dates
        $invoiceDate = $options['invoice_date'] ?? now();
        $dueDate = $options['due_date'] ?? $invoiceDate->copy()->addDays(30);

        // Create invoice as draft
        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'subtotal' => $contractPayment->amount,
            'tax_amount' => $options['tax_amount'] ?? 0,
            'total_amount' => $contractPayment->amount + ($options['tax_amount'] ?? 0),
            'customer_id' => $contract->customer_id,
            'business_unit_id' => $contract->business_unit_id ?? 1,
            'invoice_sequence_id' => $sequence->id,
            'created_by' => auth()->id(),
            'notes' => $options['notes'] ?? null,
            'terms_conditions' => $options['terms_conditions'] ?? null,
            'reference' => $contract->contract_number,
        ]);

        // Create invoice item linked to contract payment
        $invoice->items()->create([
            'description' => $contractPayment->name ?: "Payment for Contract {$contract->contract_number}",
            'quantity' => 1,
            'unit_price' => $contractPayment->amount,
            'total' => $contractPayment->amount,
            'contract_payment_id' => $contractPayment->id,
        ]);

        return $invoice;
    }

    /**
     * Generate invoices from multiple contract payments.
     */
    public function generateFromContractPayments(array $contractPaymentIds, array $options = []): array
    {
        $invoices = [];
        $contractPayments = ContractPayment::with('contract.customer')->whereIn('id', $contractPaymentIds)->get();

        foreach ($contractPayments as $contractPayment) {
            try {
                $invoices[] = $this->generateFromContractPayment($contractPayment, $options);
            } catch (\Exception $e) {
                // Log error and continue with next payment
                \Log::error("Failed to generate invoice for contract payment {$contractPayment->id}: " . $e->getMessage());
            }
        }

        return $invoices;
    }

    /**
     * Generate invoice for entire contract.
     */
    public function generateFromContract(Contract $contract, array $options = []): Invoice
    {
        // Find appropriate sequence
        $sequence = $this->findSequenceForContract($contract);
        if (!$sequence) {
            throw new \Exception('No suitable invoice sequence found for this contract.');
        }

        // Use draft placeholder - real number assigned when invoice is sent
        $invoiceNumber = Invoice::generateDraftNumber();

        // Calculate dates
        $invoiceDate = $options['invoice_date'] ?? now();
        $dueDate = $options['due_date'] ?? $invoiceDate->copy()->addDays(30);

        // Calculate total from pending payments or full contract amount
        $totalAmount = $options['amount'] ?? $contract->total_amount;

        // Create invoice as draft
        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'subtotal' => $totalAmount,
            'tax_amount' => $options['tax_amount'] ?? 0,
            'total_amount' => $totalAmount + ($options['tax_amount'] ?? 0),
            'customer_id' => $contract->customer_id,
            'business_unit_id' => $contract->business_unit_id ?? 1,
            'invoice_sequence_id' => $sequence->id,
            'created_by' => auth()->id(),
            'notes' => $options['notes'] ?? null,
            'terms_conditions' => $options['terms_conditions'] ?? null,
            'reference' => $contract->contract_number,
        ]);

        // Create invoice item for the contract
        $invoice->items()->create([
            'description' => $options['description'] ?? "Services for Contract {$contract->contract_number} - {$contract->description}",
            'quantity' => 1,
            'unit_price' => $totalAmount,
            'total' => $totalAmount,
        ]);

        return $invoice;
    }

    /**
     * Generate invoices from scheduled payments (batch processing).
     */
    public function generateFromScheduledPayments(Carbon $fromDate = null, Carbon $toDate = null): array
    {
        $fromDate = $fromDate ?? now()->startOfMonth();
        $toDate = $toDate ?? now()->endOfMonth();

        // Get contract payments that are due and not yet invoiced
        $contractPayments = ContractPayment::with('contract.customer')
            ->where('status', 'pending')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$fromDate, $toDate])
            ->whereDoesntHave('invoiceItem') // Not already invoiced
            ->get();

        $invoices = [];
        foreach ($contractPayments as $contractPayment) {
            try {
                $invoice = $this->generateFromContractPayment($contractPayment, [
                    'notes' => 'Auto-generated from scheduled payment',
                ]);
                $invoices[] = $invoice;
            } catch (\Exception $e) {
                \Log::error("Failed to auto-generate invoice for payment {$contractPayment->id}: " . $e->getMessage());
            }
        }

        return $invoices;
    }

    /**
     * Find appropriate invoice sequence for a contract.
     */
    private function findSequenceForContract(Contract $contract): ?InvoiceSequence
    {
        $businessUnit = $contract->businessUnit;
        $sectorId = $businessUnit->sector_id;

        // Try to find sequence for specific business unit and sector
        $sequence = InvoiceSequence::active()
            ->where('business_unit_id', $contract->business_unit_id)
            ->when($sectorId, function ($query) use ($sectorId) {
                $query->whereJsonContains('sector_ids', $sectorId);
            })
            ->first();

        // Fall back to sequences for the sector only
        if (!$sequence && $sectorId) {
            $sequence = InvoiceSequence::active()
                ->whereNull('business_unit_id')
                ->whereJsonContains('sector_ids', $sectorId)
                ->first();
        }

        // Fall back to global sequences
        if (!$sequence) {
            $sequence = InvoiceSequence::active()
                ->whereNull('business_unit_id')
                ->whereNull('sector_ids')
                ->first();
        }

        return $sequence;
    }

    /**
     * Get preview of invoice that would be generated from contract payment.
     */
    public function previewFromContractPayment(ContractPayment $contractPayment, array $options = []): array
    {
        $contract = $contractPayment->contract;
        $sequence = $this->findSequenceForContract($contract);

        if (!$sequence) {
            throw new \Exception('No suitable invoice sequence found for this contract.');
        }

        $invoiceDate = $options['invoice_date'] ?? now();
        $dueDate = $options['due_date'] ?? $invoiceDate->copy()->addDays(30);
        $taxAmount = $options['tax_amount'] ?? 0;

        return [
            'invoice_number_preview' => $sequence->previewNextInvoiceNumber(),
            'invoice_date' => $invoiceDate->format('Y-m-d'),
            'due_date' => $dueDate->format('Y-m-d'),
            'customer' => $contract->customer,
            'business_unit' => $contract->businessUnit,
            'subtotal' => $contractPayment->amount,
            'tax_amount' => $taxAmount,
            'total_amount' => $contractPayment->amount + $taxAmount,
            'items' => [
                [
                    'description' => $contractPayment->name ?: "Payment for Contract {$contract->contract_number}",
                    'quantity' => 1,
                    'unit_price' => $contractPayment->amount,
                    'total' => $contractPayment->amount,
                ]
            ],
        ];
    }

    /**
     * Check if contract payment can be invoiced.
     */
    public function canInvoiceContractPayment(ContractPayment $contractPayment): bool
    {
        // Check if payment is pending
        if ($contractPayment->status !== 'pending') {
            return false;
        }

        // Check if already invoiced
        if ($contractPayment->invoiceItem()->exists()) {
            return false;
        }

        // Check if contract is active
        if ($contractPayment->contract->status !== 'active') {
            return false;
        }

        return true;
    }
}