<?php

namespace Modules\Payroll\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Collection;

/**
 * BankSheetExport
 *
 * Handles the generation of Excel files for bank payroll submission.
 * Formats the data according to bank specifications with required columns:
 * - Beneficiary Account No. (11 or 13 digits)
 * - Beneficiary Name
 * - Transaction Currency (EGP/USD)
 * - Payment Amount
 * - Employee ID (10 digits - bank employee ID)
 *
 * @author Dev Agent
 */
class BankSheetExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithStyles
{
    /**
     * @var Collection
     */
    private $payrollRuns;

    /**
     * @var string
     */
    private $periodLabel;

    /**
     * Constructor
     *
     * @param Collection $payrollRuns Collection of PayrollRun models
     * @param string $periodLabel Human-readable period label (e.g., "June 2025")
     */
    public function __construct(Collection $payrollRuns, string $periodLabel)
    {
        $this->payrollRuns = $payrollRuns;
        $this->periodLabel = $periodLabel;
    }

    /**
     * Return the collection of payroll runs to export.
     *
     * @return Collection
     */
    public function collection()
    {
        return $this->payrollRuns;
    }

    /**
     * Define the headings for the Excel file.
     * These match the bank's required format exactly.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Beneficiary Account No. (Mandatory Field) (Length 11 or 13 Digit)',
            'Beneficiary Name',
            'Transaction Currency (Mandatory Field) (EGP,USD in Capital Letter)',
            'Payment Amount (Mandatory Field) (Example: 1000.55)',
            'Employee ID (Mandatory Field) (Length 10 Digit)',
        ];
    }

    /**
     * Map each payroll run to the required bank format.
     *
     * @param mixed $payrollRun
     * @return array
     */
    public function map($payrollRun): array
    {
        $employee = $payrollRun->employee;
        $bankInfo = $employee->bank_info ?? [];

        return [
            $bankInfo['account_number'] ?? '',
            $employee->name,
            strtoupper($bankInfo['currency'] ?? 'EGP'),
            round($payrollRun->final_salary, 2),
            $bankInfo['account_id'] ?? '',
        ];
    }

    /**
     * Apply column formatting.
     * Account number and Employee ID as text to preserve leading zeros.
     * Payment amount as number with 2 decimal places.
     *
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT, // Account number as text
            'D' => NumberFormat::FORMAT_NUMBER_00, // Payment amount with 2 decimals
            'E' => NumberFormat::FORMAT_TEXT, // Employee ID as text
        ];
    }

    /**
     * Apply styles to the worksheet.
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFEEEEEE',
                ],
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Auto-size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);

        return [];
    }
}
