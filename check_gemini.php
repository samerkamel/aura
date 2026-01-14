<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$file = storage_path('app/temp/incaura2k24.xls');
$spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($file);
$sheet = $spreadsheet->getSheetByName('Websites');
$data = $sheet->toArray();

echo "=== Looking for Gemini in Websites sheet ===\n\n";

foreach ($data as $i => $row) {
    $col0 = trim((string)($row[0] ?? ''));
    $col1 = trim((string)($row[1] ?? ''));
    $col2 = trim((string)($row[2] ?? ''));
    $total = $row[17] ?? 0;

    if (stripos($col1, 'Gemini') !== false || stripos($col0, 'Gemini') !== false) {
        echo "Row $i: [$col0] [$col1] [$col2] Total: $total\n";
    }

    // Also show Obelisk
    if (stripos($col1, 'Obelisk') !== false || stripos($col0, 'Obelisk') !== false) {
        echo "Row $i: [$col0] [$col1] [$col2] Total: $total\n";
    }
}

echo "\n=== All Gemini+Obelisk contracts in DB (Websites) ===\n";

use Modules\Accounting\Models\Contract;
use Illuminate\Support\Facades\DB;

$websiteContractIds = DB::table('contract_product')
    ->where('product_id', 5)
    ->pluck('contract_id');

$contracts = Contract::whereIn('id', $websiteContractIds)
    ->where('contract_number', 'LIKE', 'C-2024%')
    ->where(function($q) {
        $q->where('client_name', 'LIKE', '%Gemini%')
          ->orWhere('client_name', 'LIKE', '%Obelisk%');
    })
    ->with('payments')
    ->get();

$totalContract = 0;
$totalPaid = 0;

foreach ($contracts as $c) {
    $paid = $c->payments->where('status', 'paid')->sum('paid_amount');
    echo "{$c->contract_number}: {$c->client_name}\n";
    echo "  Contract: " . number_format($c->total_amount, 0) . " | Paid: " . number_format($paid, 0) . "\n";
    $totalContract += $c->total_amount;
    $totalPaid += $paid;
}

echo "\nTotal Contract: " . number_format($totalContract, 0) . " EGP\n";
echo "Total Paid: " . number_format($totalPaid, 0) . " EGP\n";
