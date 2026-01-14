<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Modules\Accounting\Models\Contract;
use Modules\Accounting\Models\ContractPayment;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

$file = storage_path('app/temp/incaura2k24.xls');
$spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($file);

$productMap = [
    'PHP' => 2,
    'Mobile' => 3,
    'Websites' => 5,
    'Design' => 6,
];

$balanceContracts = [];

// Find all customers with payments but no contracts
foreach ($productMap as $sheetName => $productId) {
    $sheet = $spreadsheet->getSheetByName($sheetName);
    $data = $sheet->toArray();

    $currentCustomer = null;
    $contractVal = 0;
    $paidVal = 0;
    $balanceVal = 0;

    // Get month columns for payment breakdown
    $monthlyPaid = [];

    foreach ($data as $i => $row) {
        $col0 = trim((string)($row[0] ?? ''));
        $col1 = trim((string)($row[1] ?? ''));
        $col2 = trim((string)($row[2] ?? ''));
        $total = $row[17] ?? 0;
        if (is_string($total)) $total = (float) preg_replace('/[^0-9.-]/', '', $total);

        if (is_numeric($col0) && $col1 && $col1 !== 'إجمالى') {
            // Save previous customer if has payments but no contract
            if ($currentCustomer && $contractVal == 0 && $paidVal > 0) {
                $balanceContracts[] = [
                    'customer_name' => $currentCustomer,
                    'product' => $sheetName,
                    'product_id' => $productId,
                    'balance' => $balanceVal,
                    'paid' => $paidVal,
                    'monthly_paid' => $monthlyPaid,
                ];
            }

            $currentCustomer = $col1;
            $contractVal = 0;
            $paidVal = 0;
            $balanceVal = 0;
            $monthlyPaid = [];
        }

        if ($currentCustomer) {
            if ($col2 === 'رصيد') {
                $balanceVal = $total;
            }
            if ($col2 === 'تعاقد') {
                $contractVal = $total;
            }
            if ($col2 === 'مدفوع') {
                $paidVal = $total;
                // Get monthly breakdown
                for ($m = 5; $m <= 16; $m++) {
                    $monthVal = $row[$m] ?? 0;
                    if (is_string($monthVal)) {
                        $monthVal = (float) preg_replace('/[^0-9.-]/', '', $monthVal);
                    }
                    if ($monthVal > 0) {
                        $monthlyPaid[$m - 4] = $monthVal; // Month 1-12
                    }
                }
            }
        }
    }

    // Last customer
    if ($currentCustomer && $contractVal == 0 && $paidVal > 0) {
        $balanceContracts[] = [
            'customer_name' => $currentCustomer,
            'product' => $sheetName,
            'product_id' => $productId,
            'balance' => $balanceVal,
            'paid' => $paidVal,
            'monthly_paid' => $monthlyPaid,
        ];
    }
}

echo "=== Creating Balance Contracts ===\n\n";

// Get next contract number
$lastContract = Contract::where('contract_number', 'LIKE', 'C-2024%')
    ->orderByRaw('CAST(SUBSTRING(contract_number, 7) AS UNSIGNED) DESC')
    ->first();

$nextNum = $lastContract ? ((int) substr($lastContract->contract_number, 6)) + 1 : 1;

DB::beginTransaction();

try {
    foreach ($balanceContracts as $bc) {
        $contractNumber = 'C-2024' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        // Find or create customer
        $customer = Customer::where('company_name', 'LIKE', '%' . $bc['customer_name'] . '%')
            ->orWhere('name', 'LIKE', '%' . $bc['customer_name'] . '%')
            ->first();

        // Create contract
        $contract = Contract::create([
            'contract_number' => $contractNumber,
            'client_name' => $bc['customer_name'],
            'customer_id' => $customer?->id,
            'total_amount' => 0,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'completed',
            'is_active' => true,
            'description' => 'Balance payments from prior years (' . $bc['product'] . ')',
        ]);

        // Attach product
        $contract->products()->attach($bc['product_id'], [
            'allocation_type' => 'percentage',
            'allocation_percentage' => 100,
        ]);

        // Create payments from monthly breakdown
        $monthNames = [1=>'January', 2=>'February', 3=>'March', 4=>'April', 5=>'May', 6=>'June',
                       7=>'July', 8=>'August', 9=>'September', 10=>'October', 11=>'November', 12=>'December'];
        foreach ($bc['monthly_paid'] as $month => $amount) {
            ContractPayment::create([
                'contract_id' => $contract->id,
                'name' => 'Balance Payment - ' . $monthNames[$month] . ' 2024',
                'amount' => $amount,
                'due_date' => sprintf('2024-%02d-15', $month),
                'paid_date' => sprintf('2024-%02d-15', $month),
                'paid_amount' => $amount,
                'status' => 'paid',
                'notes' => 'Balance payment from prior years',
            ]);
        }

        echo "$contractNumber: {$bc['customer_name']} ({$bc['product']})\n";
        echo "  Balance: " . number_format($bc['balance'], 0) . " | Paid: " . number_format($bc['paid'], 0) . "\n";
        echo "  Payments created: " . count($bc['monthly_paid']) . "\n\n";

        $nextNum++;
    }

    DB::commit();
    echo "=== Done! Created " . count($balanceContracts) . " balance contracts ===\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
