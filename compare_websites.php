<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Modules\Accounting\Models\Contract;
use Modules\Accounting\Models\ContractPayment;
use Illuminate\Support\Facades\DB;

// Get all Websites contracts paid amounts
$websiteContractIds = DB::table('contract_product')
    ->where('product_id', 5)
    ->pluck('contract_id');

$dbCustomerPaid = [];
$contracts = Contract::whereIn('id', $websiteContractIds)
    ->where('contract_number', 'LIKE', 'C-2024%')
    ->with('payments')
    ->get();

foreach ($contracts as $c) {
    $clientName = trim($c->client_name);
    $paid = $c->payments->where('status', 'paid')->sum('paid_amount');
    if (!isset($dbCustomerPaid[$clientName])) {
        $dbCustomerPaid[$clientName] = 0;
    }
    $dbCustomerPaid[$clientName] += $paid;
}

// Get Excel Websites paid amounts
$file = storage_path('app/temp/incaura2k24.xls');
$spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($file);
$sheet = $spreadsheet->getSheetByName('Websites');
$data = $sheet->toArray();

$excelCustomerPaid = [];
$currentCustomer = null;

foreach ($data as $row) {
    $col0 = trim((string)($row[0] ?? ''));
    $col1 = trim((string)($row[1] ?? ''));
    $col2 = trim((string)($row[2] ?? ''));
    $total = $row[17] ?? 0;
    if (is_string($total)) {
        $total = (float) preg_replace('/[^0-9.-]/', '', $total);
    }

    if (is_numeric($col0) && $col1 && $col1 !== 'إجمالى') {
        $currentCustomer = $col1;
    }

    if ($currentCustomer && $col2 === 'مدفوع') {
        if (!isset($excelCustomerPaid[$currentCustomer])) {
            $excelCustomerPaid[$currentCustomer] = 0;
        }
        $excelCustomerPaid[$currentCustomer] += $total;
    }
}

// Compare
echo "=== Websites Per-Customer Comparison ===\n\n";
echo str_pad("Customer", 35) . str_pad("Excel", 12) . str_pad("DB", 12) . "Diff\n";
echo str_repeat("-", 70) . "\n";

$allCustomers = array_unique(array_merge(array_keys($excelCustomerPaid), array_keys($dbCustomerPaid)));
sort($allCustomers);

$discrepancies = [];
foreach ($allCustomers as $customer) {
    $excel = $excelCustomerPaid[$customer] ?? 0;
    $db = $dbCustomerPaid[$customer] ?? 0;
    $diff = $db - $excel;

    if (abs($diff) > 1) {
        $discrepancies[$customer] = ['excel' => $excel, 'db' => $db, 'diff' => $diff];
    }
}

// Sort by absolute diff
uasort($discrepancies, function($a, $b) {
    return abs($b['diff']) - abs($a['diff']);
});

echo "\n=== Discrepancies Only (sorted by size) ===\n\n";
foreach ($discrepancies as $customer => $data) {
    $sign = $data['diff'] >= 0 ? '+' : '';
    echo str_pad(mb_substr($customer, 0, 35), 35);
    echo str_pad(number_format($data['excel'], 0), 12);
    echo str_pad(number_format($data['db'], 0), 12);
    echo $sign . number_format($data['diff'], 0) . "\n";
}

echo "\nTotal discrepancies: " . count($discrepancies) . "\n";
echo "Sum of differences: " . number_format(array_sum(array_column($discrepancies, 'diff')), 0) . "\n";
