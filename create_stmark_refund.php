<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Modules\Accounting\Models\Contract;
use Modules\Accounting\Models\ContractPayment;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

// Get next contract number
$lastContract = Contract::where('contract_number', 'LIKE', 'C-2024%')
    ->orderByRaw('CAST(SUBSTRING(contract_number, 7) AS UNSIGNED) DESC')
    ->first();

$nextNum = $lastContract ? ((int) substr($lastContract->contract_number, 6)) + 1 : 1;
$contractNumber = 'C-2024' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

DB::beginTransaction();

try {
    // Find or create customer
    $customer = Customer::where('company_name', 'LIKE', '%St Mark%')
        ->orWhere('name', 'LIKE', '%St Mark%')
        ->first();

    // Create contract
    $contract = Contract::create([
        'contract_number' => $contractNumber,
        'client_name' => 'St Mark',
        'customer_id' => $customer?->id,
        'total_amount' => 0,
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'status' => 'completed',
        'is_active' => false,
        'description' => 'Refund - Closed account (Websites)',
    ]);

    // Attach Websites product (ID 5)
    $contract->products()->attach(5, [
        'allocation_type' => 'percentage',
        'allocation_percentage' => 100,
    ]);

    // Create refund payment (negative amount)
    ContractPayment::create([
        'contract_id' => $contract->id,
        'name' => 'Refund - St Mark (Closed)',
        'amount' => -13600,
        'due_date' => '2024-01-15',
        'paid_date' => '2024-01-15',
        'paid_amount' => -13600,
        'status' => 'paid',
        'notes' => 'Refund for closed account',
    ]);

    DB::commit();

    echo "=== St Mark Refund Created ===\n\n";
    echo "Contract: $contractNumber\n";
    echo "Client: St Mark\n";
    echo "Refund Amount: -13,600 EGP\n";
    echo "Product: Websites\n";
    echo "\nDone!\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
