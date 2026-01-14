<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$file = storage_path('app/temp/incaura2k24.xls');
$spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($file);
$sheet = $spreadsheet->getSheetByName('Websites');
$data = $sheet->toArray();

echo "=== All Gemini blocks in Websites sheet ===\n\n";

$currentCustomer = null;
$customerNum = null;
$customerRows = [];

foreach ($data as $i => $row) {
    $col0 = trim((string)($row[0] ?? ''));
    $col1 = trim((string)($row[1] ?? ''));
    $col2 = trim((string)($row[2] ?? ''));
    $total = $row[17] ?? 0;

    // New customer starts with numeric col0 and non-empty col1
    if (is_numeric($col0) && $col1 && $col1 !== 'إجمالى') {
        // Print previous customer if it was Gemini
        if ($currentCustomer && (stripos($currentCustomer, 'Gemini') !== false || stripos($currentCustomer, 'Obelisk') !== false)) {
            echo "Customer #$customerNum: $currentCustomer\n";
            foreach ($customerRows as $cr) {
                echo "  {$cr['op']}: " . number_format($cr['total'], 0) . " EGP\n";
            }
            echo "\n";
        }
        $currentCustomer = $col1;
        $customerNum = $col0;
        $customerRows = [];
    }

    // Collect operation rows
    if ($currentCustomer && $col2) {
        if (is_string($total)) {
            $total = (float) preg_replace('/[^0-9.-]/', '', $total);
        }
        $customerRows[] = ['op' => $col2, 'total' => $total];
    }
}

// Check last customer
if ($currentCustomer && (stripos($currentCustomer, 'Gemini') !== false || stripos($currentCustomer, 'Obelisk') !== false)) {
    echo "Customer #$customerNum: $currentCustomer\n";
    foreach ($customerRows as $cr) {
        echo "  {$cr['op']}: " . number_format($cr['total'], 0) . " EGP\n";
    }
}
