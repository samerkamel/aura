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
 * Employee Name, Bank Account Number, and Final Salary Amount.
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
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Employee Name',
            'Bank Account Number',
            'Final Salary Amount',
        ];
    }

    /**
     * Map each payroll run to the required format.
     *
     * @param mixed $payrollRun
     * @return array
     */
    public function map($payrollRun): array
    {
        return [
            $payrollRun->employee->name,
            $payrollRun->employee->bank_account_number ?? 'N/A',
            $payrollRun->final_salary,
        ];
    }

    /**
     * Apply column formatting.
     *
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE, // Final Salary Amount column
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
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
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

        // Add a title row above the headers
        $sheet->insertNewRowBefore(1);
        $sheet->setCellValue('A1', "Payroll Export - {$this->periodLabel}");
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        return [];
    }
}
