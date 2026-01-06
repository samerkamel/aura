<?php

namespace Modules\Invoicing\Observers;

use App\Services\InvoiceProjectSyncService;
use Modules\Invoicing\Models\Invoice;
use Illuminate\Support\Facades\Log;

/**
 * Observer for Invoice model events.
 *
 * Automatically syncs invoices to project revenues when:
 * - An invoice is created (with project allocations)
 * - An invoice is updated (especially status changes)
 * - An invoice is deleted
 */
class InvoiceObserver
{
    protected InvoiceProjectSyncService $syncService;

    public function __construct(InvoiceProjectSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        // Only sync if invoice has project allocations and is not a draft
        if ($invoice->status !== 'draft') {
            $this->syncInvoiceToProjects($invoice, 'created');
        }
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        // Check if status changed to paid
        if ($invoice->wasChanged('status') && $invoice->status === 'paid') {
            $this->syncService->onInvoicePaid($invoice);
            return;
        }

        // Check if status changed from draft to sent (invoice sent)
        if ($invoice->wasChanged('status') && $invoice->status === 'sent') {
            $this->syncInvoiceToProjects($invoice, 'sent');
            return;
        }

        // For other updates (amount changes, etc.), sync to all linked projects
        if ($invoice->wasChanged(['total_amount', 'paid_amount', 'status'])) {
            $this->syncService->syncInvoiceStatusChange($invoice);
        }
    }

    /**
     * Handle the Invoice "deleted" event.
     */
    public function deleted(Invoice $invoice): void
    {
        $this->deleteLinkedRevenues($invoice);
    }

    /**
     * Sync an invoice to all projects linked to it.
     */
    protected function syncInvoiceToProjects(Invoice $invoice, string $action): void
    {
        try {
            $result = $this->syncService->syncInvoiceToProjects($invoice);

            if ($result['success'] && $result['synced'] > 0) {
                Log::info("Invoice {$invoice->id} synced to projects ({$action})", [
                    'invoice_id' => $invoice->id,
                    'synced_count' => $result['synced'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error syncing invoice {$invoice->id} to projects", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete project revenues linked to this invoice.
     */
    protected function deleteLinkedRevenues(Invoice $invoice): void
    {
        try {
            $result = $this->syncService->removeInvoiceRevenues($invoice);

            if ($result['removed'] > 0) {
                Log::info("Deleted {$result['removed']} project revenue(s) for deleted invoice {$invoice->id}");
            }
        } catch (\Exception $e) {
            Log::error("Error deleting revenues for invoice {$invoice->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
