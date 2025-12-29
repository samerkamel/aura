<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use Modules\Invoicing\Models\Invoice;

// Build customer map
$customerMap = [];
$customers = Customer::whereNotNull('perfex_id')->get(['id', 'perfex_id']);
foreach ($customers as $customer) {
    $customerMap[$customer->perfex_id] = $customer->id;
}

echo "Customer map has " . count($customerMap) . " entries\n";
echo "Map entry for perfex_id 14: " . ($customerMap[14] ?? 'NOT FOUND') . "\n";

// Extract first invoice
$content = file_get_contents("/Users/SamerKamel/Root/aura/docs/2025-12-29-14-27-16_backup.sql");
$tableName = 'tblinvoices';

// Get columns
$columnsPattern = '/INSERT INTO `?' . preg_quote($tableName, '/') . '`?\s+\(([^)]+)\)\s+VALUES/i';
preg_match($columnsPattern, $content, $colMatch);
preg_match_all('/`(\w+)`/', $colMatch[1] ?? '', $colNames);
$columns = $colNames[1] ?? [];

echo "Found " . count($columns) . " columns\n";

// Get first row
$pattern = '/INSERT INTO `?' . preg_quote($tableName, '/') . '`?\s+\([^)]+\)\s+VALUES\s*(\([^\n]+\));/im';
preg_match($pattern, $content, $match);

$valuesBlock = $match[1] ?? '';
$rowContent = trim($valuesBlock);
if (str_starts_with($rowContent, '(')) {
    $rowContent = substr($rowContent, 1);
}
if (str_ends_with($rowContent, ')')) {
    $rowContent = substr($rowContent, 0, -1);
}

echo "Row content length: " . strlen($rowContent) . "\n";

// Parse values - simple version
function parseRowValues(string $row): array
{
    $values = [];
    $current = '';
    $inString = false;
    $stringChar = null;
    $escaped = false;

    for ($i = 0; $i < strlen($row); $i++) {
        $char = $row[$i];

        if ($escaped) {
            $current .= $char;
            $escaped = false;
            continue;
        }

        if ($char === '\\') {
            $escaped = true;
            continue;
        }

        if (!$inString && ($char === "'" || $char === '"')) {
            $inString = true;
            $stringChar = $char;
            continue;
        }

        if ($inString && $char === $stringChar) {
            $inString = false;
            $stringChar = null;
            continue;
        }

        if (!$inString && $char === ',') {
            $values[] = trim($current) === 'NULL' ? null : trim($current);
            $current = '';
            continue;
        }

        $current .= $char;
    }

    if (strlen(trim($current)) > 0 || count($values) > 0) {
        $values[] = trim($current) === 'NULL' ? null : trim($current);
    }

    return $values;
}

$values = parseRowValues($rowContent);
echo "Parsed " . count($values) . " values\n";

if (count($columns) === count($values)) {
    $invoice = array_combine($columns, $values);
    echo "Invoice ID: " . ($invoice['id'] ?? 'N/A') . "\n";
    echo "Client ID: " . ($invoice['clientid'] ?? 'N/A') . "\n";

    $clientId = $invoice['clientid'] ?? null;
    $customerId = $customerMap[$clientId] ?? null;
    echo "Mapped to customer ID: " . ($customerId ?? 'NULL') . "\n";

    // Check if invoice already exists
    $perfexId = $invoice['id'] ?? null;
    $existing = Invoice::where('perfex_id', $perfexId)->first();
    echo "Existing invoice with perfex_id $perfexId: " . ($existing ? 'YES' : 'NO') . "\n";
} else {
    echo "Column/value count mismatch: " . count($columns) . " vs " . count($values) . "\n";
}
