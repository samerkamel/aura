<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$file = storage_path('app/temp/incaura2k24.xls');
$spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($file);
$sheet = $spreadsheet->getSheetByName('Websites');
$data = $sheet->toArray();

echo "=== Looking for St Mark in Websites sheet ===\n\n";

$currentCustomer = null;
foreach ($data as $i => $row) {
    $col0 = trim((string)($row[0] ?? ''));
    $col1 = trim((string)($row[1] ?? ''));
    $col2 = trim((string)($row[2] ?? ''));
    $total = $row[17] ?? 0;

    if (is_numeric($col0) && $col1 && $col1 !== 'إجمالى') {
        $currentCustomer = $col1;
    }

    if ($currentCustomer && stripos($currentCustomer, 'Mark') !== false) {
        echo "Row $i: [$col0] [$col1] [$col2] Total: $total\n";
    }
}

echo "\n=== St Mark contracts in DB ===\n";
use Modules\Accounting\Models\Contract;

$contracts = Contract::where('client_name', 'LIKE', '%Mark%')
    ->where('contract_number', 'LIKE', 'C-2024%')
    ->with('payments')
    ->get();

foreach ($contracts as $c) {
    $paid = $c->payments->where('status', 'paid')->sum('paid_amount');
    echo "{$c->contract_number}: {$c->client_name}\n";
    echo "  Contract: " . number_format($c->total_amount, 0) . " | Paid: " . number_format($paid, 0) . "\n";
}
