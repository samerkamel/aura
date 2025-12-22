<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\ExpenseCategory;
use Modules\Accounting\Models\ExpenseCategoryBudget;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * The current budget year.
     */
    protected int $budgetYear;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->budgetYear = (int) date('Y');

        // Tier 1 Categories (from Total Revenue = 23.1%)
        $this->seedTier1Categories();

        // Tier 2 Categories (from Net Income / عائد الدخل = 76.9% of revenue)
        $this->seedTier2Categories();
    }

    /**
     * Seed Tier 1 categories (calculated from Total Revenue).
     */
    protected function seedTier1Categories(): void
    {
        $tier1Categories = [
            [
                'name' => 'VAT',
                'name_ar' => 'ضريبة القيمة المضافة',
                'color' => '#FF6B6B',
                'budget_percentage' => 13.00,
                'description' => 'Value Added Tax payments',
            ],
            [
                'name' => 'Total Cost of Sales',
                'name_ar' => 'إجمالي تكلفة المبيعات',
                'color' => '#4ECDC4',
                'budget_percentage' => 9.50,
                'description' => 'Total cost of sales including all sub-categories',
                'subcategories' => [
                    [
                        'name' => 'Imported CoS',
                        'name_ar' => 'تكلفة المبيعات المستوردة',
                        'color' => '#45B7D1',
                    ],
                    [
                        'name' => 'Local CoS',
                        'name_ar' => 'تكلفة المبيعات المحلية',
                        'color' => '#96CEB4',
                    ],
                    [
                        'name' => 'Internal CoS',
                        'name_ar' => 'تكلفة المبيعات الداخلية',
                        'color' => '#FFEAA7',
                    ],
                ],
            ],
            [
                'name' => 'Consultancy',
                'name_ar' => 'استشارات',
                'color' => '#DDA0DD',
                'budget_percentage' => 0.30,
                'description' => 'External consultancy fees',
            ],
            [
                'name' => 'Transportation',
                'name_ar' => 'نقل',
                'color' => '#98D8C8',
                'budget_percentage' => 0.30,
                'description' => 'Transportation and logistics costs',
            ],
            [
                'name' => 'Operations',
                'name_ar' => 'عمليات',
                'color' => '#F7DC6F',
                'budget_percentage' => 0.00,
                'description' => 'Operational expenses',
            ],
            [
                'name' => 'Warranty Cost',
                'name_ar' => 'تكلفة الضمان',
                'color' => '#BB8FCE',
                'budget_percentage' => 0.00,
                'description' => 'Warranty and after-sales service costs',
            ],
        ];

        foreach ($tier1Categories as $index => $categoryData) {
            $this->createCategoryWithBudget(
                $categoryData,
                ExpenseCategoryBudget::BASE_TOTAL_REVENUE,
                $index + 1
            );
        }
    }

    /**
     * Seed Tier 2 categories (calculated from Net Income / عائد الدخل).
     */
    protected function seedTier2Categories(): void
    {
        $tier2Categories = [
            [
                'name' => 'Income Tax & Social Insurance',
                'name_ar' => 'ضريبة الدخل والتأمينات الاجتماعية',
                'color' => '#E74C3C',
                'budget_percentage' => 9.50,
                'description' => 'Corporate income tax and social insurance contributions',
            ],
            [
                'name' => 'Payroll',
                'name_ar' => 'الرواتب',
                'color' => '#3498DB',
                'budget_percentage' => 57.40,
                'description' => 'Employee salaries and wages',
            ],
            [
                'name' => 'Incentives',
                'name_ar' => 'حوافز',
                'color' => '#2ECC71',
                'budget_percentage' => 4.00,
                'description' => 'Employee bonuses and incentives',
            ],
            [
                'name' => 'Installations',
                'name_ar' => 'تركيبات',
                'color' => '#9B59B6',
                'budget_percentage' => 0.00,
                'description' => 'Installation and setup costs',
            ],
            [
                'name' => 'Transport Fees',
                'name_ar' => 'رسوم النقل',
                'color' => '#1ABC9C',
                'budget_percentage' => 0.00,
                'description' => 'Transportation fees and allowances',
            ],
            [
                'name' => 'Marketing',
                'name_ar' => 'تسويق',
                'color' => '#E91E63',
                'budget_percentage' => 0.00,
                'description' => 'Marketing and advertising expenses',
            ],
            [
                'name' => 'Business License',
                'name_ar' => 'مزاولة نشاط',
                'color' => '#FF9800',
                'budget_percentage' => 0.20,
                'description' => 'Business license and activity permits',
            ],
            [
                'name' => 'Training',
                'name_ar' => 'تدريب',
                'color' => '#00BCD4',
                'budget_percentage' => 2.00,
                'description' => 'Employee training and development',
            ],
            [
                'name' => 'Equipment',
                'name_ar' => 'معدات',
                'color' => '#795548',
                'budget_percentage' => 3.00,
                'description' => 'Equipment and tools',
            ],
            [
                'name' => 'Product Updates',
                'name_ar' => 'تحديثات المنتجات',
                'color' => '#607D8B',
                'budget_percentage' => 0.00,
                'description' => 'Product development and updates',
            ],
            [
                'name' => 'Taxes',
                'name_ar' => 'ضرائب',
                'color' => '#F44336',
                'budget_percentage' => 2.00,
                'description' => 'Other taxes and duties',
            ],
            [
                'name' => 'Fixed Expenses',
                'name_ar' => 'مصاريف ثابتة',
                'color' => '#673AB7',
                'budget_percentage' => 10.00,
                'description' => 'Fixed monthly expenses',
                'subcategories' => [
                    [
                        'name' => 'Rent',
                        'name_ar' => 'إيجار',
                        'color' => '#9C27B0',
                    ],
                ],
            ],
            [
                'name' => 'Medical Insurance',
                'name_ar' => 'تأمين طبي',
                'color' => '#4CAF50',
                'budget_percentage' => 1.40,
                'description' => 'Employee medical insurance',
            ],
            [
                'name' => 'Profit Share',
                'name_ar' => 'حصة الأرباح',
                'color' => '#FFC107',
                'budget_percentage' => 10.50,
                'description' => 'Profit sharing and dividends',
            ],
        ];

        $startIndex = ExpenseCategory::mainCategories()->count() + 1;

        foreach ($tier2Categories as $index => $categoryData) {
            $this->createCategoryWithBudget(
                $categoryData,
                ExpenseCategoryBudget::BASE_NET_INCOME,
                $startIndex + $index
            );
        }
    }

    /**
     * Create a category with its budget and optional subcategories.
     */
    protected function createCategoryWithBudget(
        array $data,
        string $calculationBase,
        int $sortOrder
    ): ExpenseCategory {
        $subcategories = $data['subcategories'] ?? [];
        unset($data['subcategories']);

        $budgetPercentage = $data['budget_percentage'] ?? 0;
        unset($data['budget_percentage']);

        // Create or update the main category
        $category = ExpenseCategory::updateOrCreate(
            ['name' => $data['name']],
            array_merge($data, [
                'is_active' => true,
                'sort_order' => $sortOrder,
            ])
        );

        // Create or update the budget for this year (always create for main categories)
        ExpenseCategoryBudget::updateOrCreate(
            [
                'expense_category_id' => $category->id,
                'budget_year' => $this->budgetYear,
            ],
            [
                'budget_percentage' => $budgetPercentage,
                'calculation_base' => $calculationBase,
            ]
        );

        // Create subcategories
        foreach ($subcategories as $subIndex => $subData) {
            ExpenseCategory::updateOrCreate(
                ['name' => $subData['name'], 'parent_id' => $category->id],
                array_merge($subData, [
                    'is_active' => true,
                    'parent_id' => $category->id,
                    'sort_order' => $subIndex + 1,
                ])
            );
        }

        return $category;
    }
}
