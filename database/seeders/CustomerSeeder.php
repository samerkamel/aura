<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds customers from the CSV export file.
     */
    public function run(): void
    {
        $csvPath = base_path('docs/fingerprint/Customers.csv');

        if (!File::exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");
            return;
        }

        $this->command->info('Starting customer import from CSV...');

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            $this->command->error("Could not open CSV file");
            return;
        }

        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            $this->command->error("Could not read CSV headers");
            fclose($handle);
            return;
        }

        // Clean headers (remove BOM if present)
        $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);
        $headers = array_map('trim', $headers);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Create associative array from row
            $data = [];
            foreach ($headers as $index => $header) {
                $data[$header] = $row[$index] ?? null;
            }

            try {
                $customer = $this->createCustomerFromRow($data);
                if ($customer) {
                    $imported++;
                    $this->command->line("  Imported: {$customer->display_name}");
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors[] = "Row error: " . ($data['Company name'] ?? 'Unknown') . " - " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($handle);

        $this->command->info("Import completed!");
        $this->command->info("  Imported: {$imported}");
        $this->command->info("  Skipped: {$skipped}");

        if (!empty($errors)) {
            $this->command->warn("Errors:");
            foreach (array_slice($errors, 0, 10) as $error) {
                $this->command->warn("  - {$error}");
            }
            if (count($errors) > 10) {
                $this->command->warn("  ... and " . (count($errors) - 10) . " more errors");
            }
        }
    }

    /**
     * Create a customer from a CSV row.
     */
    private function createCustomerFromRow(array $data): ?Customer
    {
        $companyName = $this->cleanString($data['Company name'] ?? '');
        $leadName = $this->cleanString($data['Lead name'] ?? '');
        $englishName = $this->cleanString($data['English name'] ?? '');

        // Skip if no company name
        if (empty($companyName)) {
            return null;
        }

        // Check if customer already exists by company name or tax_id
        $taxId = $this->cleanString($data['Vat'] ?? '');
        if (!empty($taxId)) {
            $existing = Customer::where('tax_id', $taxId)->first();
            if ($existing) {
                return null; // Skip duplicate
            }
        }

        // Check by company name
        $existing = Customer::where('company_name', $companyName)->first();
        if ($existing) {
            return null; // Skip duplicate
        }

        // Build address from components
        $addressParts = array_filter([
            $this->cleanHtml($data['Address'] ?? ''),
            $this->cleanString($data['City'] ?? ''),
            $this->cleanString($data['State'] ?? ''),
            $this->cleanString($data['Zip'] ?? ''),
            $this->cleanString($data['Country'] ?? ''),
        ]);
        $address = implode(', ', $addressParts);

        // Build contact persons array
        $contactPersons = [];
        if (!empty($leadName)) {
            $contactPersons[] = [
                'name' => $leadName,
                'role' => 'Primary Contact',
            ];
        }

        // Build notes
        $notes = [];
        if (!empty($englishName) && $englishName !== $companyName) {
            $notes[] = "English Name: {$englishName}";
        }
        $createdBy = $this->cleanString($data['Created by'] ?? '');
        if (!empty($createdBy)) {
            $notes[] = "Created by: {$createdBy}";
        }
        $currency = $this->cleanString($data['Default currency'] ?? '');
        if (!empty($currency) && $currency !== 'EGP') {
            $notes[] = "Currency: {$currency}";
        }

        // Determine status
        $active = $data['Active'] ?? '1';
        $status = ($active === '1' || $active === 1) ? 'active' : 'inactive';

        // Determine type (company if has company name, individual otherwise)
        $type = !empty($companyName) ? 'company' : 'individual';

        // Use company name as primary name, or lead name if no company
        $name = !empty($leadName) ? $leadName : $companyName;

        // Parse created date
        $createdAt = null;
        $dateCreated = $data['Datecreated'] ?? '';
        if (!empty($dateCreated)) {
            try {
                $createdAt = \Carbon\Carbon::parse($dateCreated);
            } catch (\Exception $e) {
                $createdAt = now();
            }
        }

        // Create the customer
        $customer = Customer::create([
            'name' => $name,
            'company_name' => $companyName,
            'tax_id' => $taxId ?: null,
            'phone' => $this->cleanString($data['Phonenumber'] ?? '') ?: null,
            'address' => $address ?: null,
            'website' => $this->cleanString($data['Website'] ?? '') ?: null,
            'contact_persons' => !empty($contactPersons) ? $contactPersons : null,
            'notes' => !empty($notes) ? implode("\n", $notes) : null,
            'status' => $status,
            'type' => $type,
            'created_at' => $createdAt ?? now(),
            'updated_at' => now(),
        ]);

        return $customer;
    }

    /**
     * Clean a string value.
     */
    private function cleanString(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        // Remove invisible characters and trim
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        $value = trim($value);

        return $value;
    }

    /**
     * Clean HTML from string (convert <br /> to newlines, etc.)
     */
    private function cleanHtml(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        // Convert <br> tags to newlines
        $value = preg_replace('/<br\s*\/?>/i', "\n", $value);

        // Strip remaining HTML tags
        $value = strip_tags($value);

        // Clean up multiple newlines
        $value = preg_replace('/\n+/', "\n", $value);

        return $this->cleanString($value);
    }
}
