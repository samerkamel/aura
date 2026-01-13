<?php

namespace Modules\Accounting\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Collection;
use App\Models\Customer;
use App\Models\Product;
use Modules\Accounting\Models\Contract;

class ContractExcelImportService
{
    /**
     * Sheet name to product mapping
     */
    protected array $sheetToProductMap = [
        'PHP' => 'Custom Software',
        'Mobile' => 'Mobile Applications',
        'Websites' => 'Websites',
        '.Net' => '.NET Development',
        'Products' => 'Products',
        'Design' => 'Design',
        'Hosting' => 'Hosting',
    ];

    /**
     * Month columns mapping (0-indexed from the data start)
     */
    protected array $monthColumns = [
        1 => 'يناير',      // January
        2 => 'فبراير',     // February
        3 => 'مارس',       // March
        4 => 'أبريل',      // April
        5 => 'مايو',       // May
        6 => 'يونيو',      // June (also يونية)
        7 => 'يوليو',      // July
        8 => 'أغسطس',      // August
        9 => 'سبتمبر',     // September
        10 => 'أكتوبر',    // October
        11 => 'نوفمبر',    // November
        12 => 'ديسمبر',    // December
    ];

    /**
     * Parse Excel file and extract contract data
     */
    public function parseExcelFile(string $filePath, int $year): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $result = [
            'contracts' => [],
            'products' => [],
            'customers' => [],
            'year' => $year,
        ];

        // Get all products for mapping
        $products = Product::where('is_active', true)->get();

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            // Skip the Total sheet
            if ($sheetName === 'Total') {
                continue;
            }

            // Find matching product
            $productName = $this->sheetToProductMap[$sheetName] ?? $sheetName;
            $product = $products->first(function ($p) use ($productName, $sheetName) {
                return stripos($p->name, $productName) !== false
                    || stripos($p->name, $sheetName) !== false;
            });

            $result['products'][$sheetName] = [
                'sheet_name' => $sheetName,
                'suggested_product' => $product ? $product->name : null,
                'product_id' => $product ? $product->id : null,
            ];

            // Parse sheet data
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $sheetContracts = $this->parseSheet($sheet, $sheetName, $year);

            foreach ($sheetContracts as $contract) {
                $contract['product_sheet'] = $sheetName;
                $contract['suggested_product_id'] = $product ? $product->id : null;
                $result['contracts'][] = $contract;

                // Track unique customers
                if (!isset($result['customers'][$contract['customer_name']])) {
                    $result['customers'][$contract['customer_name']] = $this->findMatchingCustomer($contract['customer_name']);
                }
            }
        }

        return $result;
    }

    /**
     * Parse a single sheet to extract contracts
     */
    protected function parseSheet($sheet, string $sheetName, int $year): array
    {
        $contracts = [];
        $data = $sheet->toArray();

        if (count($data) < 2) {
            return $contracts;
        }

        // Find header row and column indices
        $headerRow = null;
        $customerCol = null;
        $operationCol = null;
        $startYearCol = null;
        $monthStartCol = null;

        foreach ($data as $rowIndex => $row) {
            foreach ($row as $colIndex => $cell) {
                if ($cell === 'أسم العميل' || $cell === 'Custom Software' || $cell === 'Mobile Application' || $cell === 'Websites') {
                    $customerCol = $colIndex;
                }
                if ($cell === 'العملية') {
                    $operationCol = $colIndex;
                    $headerRow = $rowIndex;
                }
                if ($cell === 'بداية السنة') {
                    $startYearCol = $colIndex;
                }
                if ($cell === 'يناير') {
                    $monthStartCol = $colIndex;
                }
            }
            if ($headerRow !== null) {
                break;
            }
        }

        // If we couldn't find the structure, try alternative detection
        if ($headerRow === null) {
            // Look for first row with customer-like data
            foreach ($data as $rowIndex => $row) {
                if ($rowIndex < 1) continue;
                // Check if this looks like a customer row (has a number in first column)
                if (isset($row[0]) && is_numeric($row[0])) {
                    $headerRow = 0;
                    $customerCol = 1;
                    $operationCol = 2;
                    $startYearCol = 4;
                    $monthStartCol = 5;
                    break;
                }
            }
        }

        if ($headerRow === null) {
            return $contracts;
        }

        // Parse customer data
        $currentCustomer = null;
        $currentCustomerData = [
            'balance' => [],
            'contract' => [],
            'expected_contract' => [],
            'paid' => [],
            'expected' => [],
        ];

        for ($i = $headerRow + 1; $i < count($data); $i++) {
            $row = $data[$i];

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Check if this is a new customer row (has customer name)
            $customerName = $row[$customerCol] ?? null;
            $operation = $row[$operationCol ?? 3] ?? null;

            // If we have a customer number in first column, this starts a new customer block
            $customerNumber = $row[0] ?? null;
            if (is_numeric($customerNumber) && $customerName && $customerName !== 'إجمالى') {
                // Save previous customer if exists
                if ($currentCustomer && !empty($currentCustomerData['contract'])) {
                    $contract = $this->buildContractFromData($currentCustomer, $currentCustomerData, $year, $monthStartCol ?? 5);
                    if ($contract) {
                        $contracts[] = $contract;
                    }
                }

                // Start new customer
                $currentCustomer = $customerName;
                $currentCustomerData = [
                    'balance' => [],
                    'contract' => [],
                    'expected_contract' => [],
                    'paid' => [],
                    'expected' => [],
                ];
            }

            // Collect operation data
            if ($currentCustomer && $operation) {
                $monthData = [];
                $startCol = $monthStartCol ?? 5;
                for ($m = 1; $m <= 12; $m++) {
                    $colIndex = $startCol + $m - 1;
                    $value = isset($row[$colIndex]) ? $this->parseNumericValue($row[$colIndex]) : 0;
                    $monthData[$m] = $value;
                }

                switch ($operation) {
                    case 'رصيد':
                        $currentCustomerData['balance'] = $monthData;
                        break;
                    case 'تعاقد':
                        $currentCustomerData['contract'] = $monthData;
                        break;
                    case 'توقع تعاقد':
                        $currentCustomerData['expected_contract'] = $monthData;
                        break;
                    case 'مدفوع':
                        $currentCustomerData['paid'] = $monthData;
                        break;
                    case 'متوقع':
                        $currentCustomerData['expected'] = $monthData;
                        break;
                }
            }
        }

        // Don't forget the last customer
        if ($currentCustomer && !empty($currentCustomerData['contract'])) {
            $contract = $this->buildContractFromData($currentCustomer, $currentCustomerData, $year, $monthStartCol ?? 5);
            if ($contract) {
                $contracts[] = $contract;
            }
        }

        return $contracts;
    }

    /**
     * Build contract data from parsed row data
     */
    protected function buildContractFromData(string $customerName, array $data, int $year, int $monthStartCol): ?array
    {
        // Find the month with contract value
        $contractMonth = null;
        $contractValue = 0;

        foreach ($data['contract'] as $month => $value) {
            if ($value > 0) {
                if ($contractMonth === null) {
                    $contractMonth = $month;
                }
                $contractValue += $value;
            }
        }

        // Skip if no contract value
        if ($contractValue <= 0) {
            return null;
        }

        // Calculate total paid
        $totalPaid = array_sum($data['paid']);

        // Build payments array
        $payments = [];
        foreach ($data['paid'] as $month => $value) {
            if ($value > 0) {
                $payments[] = [
                    'month' => $month,
                    'amount' => $value,
                    'date' => sprintf('%d-%02d-15', $year, $month),
                    'status' => 'paid',
                ];
            }
        }

        // Add expected payments
        foreach ($data['expected'] as $month => $value) {
            if ($value > 0) {
                $payments[] = [
                    'month' => $month,
                    'amount' => $value,
                    'date' => sprintf('%d-%02d-15', $year, $month),
                    'status' => 'pending',
                ];
            }
        }

        // Determine contract dates
        $startMonth = $contractMonth ?? 1;
        $startDate = sprintf('%d-%02d-01', $year, $startMonth);
        $endDate = sprintf('%d-12-31', $year);

        return [
            'customer_name' => $customerName,
            'total_amount' => $contractValue,
            'total_paid' => $totalPaid,
            'balance' => $contractValue - $totalPaid,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'contract_month' => $startMonth,
            'payments' => $payments,
            'status' => $totalPaid >= $contractValue ? 'completed' : 'active',
        ];
    }

    /**
     * Find matching customer by name
     */
    protected function findMatchingCustomer(string $customerName): ?array
    {
        // Clean up customer name
        $cleanName = trim($customerName);

        // Try exact match first
        $customer = Customer::where('company_name', 'LIKE', $cleanName)
            ->orWhere('name', 'LIKE', $cleanName)
            ->first();

        if ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->display_name,
                'match_type' => 'exact',
            ];
        }

        // Try partial match
        $customer = Customer::where('company_name', 'LIKE', "%{$cleanName}%")
            ->orWhere('name', 'LIKE', "%{$cleanName}%")
            ->first();

        if ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->display_name,
                'match_type' => 'partial',
            ];
        }

        // Try word-based matching
        $words = explode(' ', $cleanName);
        if (count($words) > 1) {
            foreach ($words as $word) {
                if (strlen($word) > 3) {
                    $customer = Customer::where('company_name', 'LIKE', "%{$word}%")
                        ->orWhere('name', 'LIKE', "%{$word}%")
                        ->first();

                    if ($customer) {
                        return [
                            'id' => $customer->id,
                            'name' => $customer->display_name,
                            'match_type' => 'word',
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Parse numeric value from Excel cell
     */
    protected function parseNumericValue($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            // Remove currency symbols, spaces, commas
            $cleaned = preg_replace('/[^0-9.-]/', '', $value);
            return (float) $cleaned;
        }

        return 0;
    }

    /**
     * Check for potential duplicate contracts
     */
    public function checkForDuplicates(array $contract, ?int $customerId, ?int $productId): ?array
    {
        if (!$customerId) {
            return null;
        }

        $query = Contract::where('customer_id', $customerId)
            ->where('total_amount', $contract['total_amount']);

        // Check date overlap
        $startDate = $contract['start_date'];
        $endDate = $contract['end_date'];

        $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });

        if ($productId) {
            $query->whereHas('products', function ($q) use ($productId) {
                $q->where('products.id', $productId);
            });
        }

        $existing = $query->first();

        if ($existing) {
            return [
                'id' => $existing->id,
                'contract_number' => $existing->contract_number,
                'customer' => $existing->customer?->display_name,
                'total_amount' => $existing->total_amount,
                'start_date' => $existing->start_date->format('Y-m-d'),
                'end_date' => $existing->end_date->format('Y-m-d'),
            ];
        }

        return null;
    }

    /**
     * Get product mapping suggestions
     */
    public function getProductMappings(): array
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $mappings = [];

        foreach ($this->sheetToProductMap as $sheetName => $suggestedName) {
            $product = $products->first(function ($p) use ($suggestedName, $sheetName) {
                return stripos($p->name, $suggestedName) !== false
                    || stripos($p->name, $sheetName) !== false;
            });

            $mappings[$sheetName] = [
                'suggested_name' => $suggestedName,
                'product_id' => $product?->id,
                'product_name' => $product?->name,
            ];
        }

        return $mappings;
    }
}
