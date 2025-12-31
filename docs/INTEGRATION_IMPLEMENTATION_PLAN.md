# Aura ERP Integration Implementation Plan

**Date**: December 31, 2025
**Status**: Approved for Implementation
**Start Date**: Fresh (no historical migration)

---

## Implementation Overview

Based on stakeholder feedback, we will implement the following integrations:

| # | Integration | Description | Priority |
|---|-------------|-------------|----------|
| 1 | Salary History Tracking | Track employee salary changes over time | Foundation |
| 2 | Payroll → Accounting | Payroll creates scheduled expense, actual on transfer | P1 |
| 3 | Project Costs → Accounting | Sync project costs to organizational expenses | P1 |
| 4 | Labor Cost Persistence | Calculate and store labor costs monthly | P1 |
| 5 | Contract → Project Revenue | Auto-create project revenue from contracts | P2 |
| 6 | Time Estimates Enhancement | Team member estimates, optional Jira pull, link to sales | P2 |

---

## Phase 1: Salary History Tracking (Foundation)

### Why First?
All labor cost calculations depend on knowing what salary was effective at a given time. Without this, historical reports will be inaccurate.

### Database Changes

**New Table: `employee_salary_history`**
```sql
CREATE TABLE employee_salary_history (
    id BIGINT PRIMARY KEY,
    employee_id BIGINT NOT NULL,
    base_salary DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EGP',
    effective_date DATE NOT NULL,
    end_date DATE NULL,  -- NULL = current salary
    reason ENUM('initial', 'annual_review', 'promotion', 'adjustment', 'correction') NOT NULL,
    notes TEXT NULL,
    approved_by BIGINT NULL,  -- user_id
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),

    INDEX idx_employee_effective (employee_id, effective_date),
    INDEX idx_effective_date (effective_date)
);
```

### Model: EmployeeSalaryHistory

**Location**: `Modules/HR/app/Models/EmployeeSalaryHistory.php`

```php
class EmployeeSalaryHistory extends Model
{
    protected $fillable = [
        'employee_id', 'base_salary', 'currency',
        'effective_date', 'end_date', 'reason',
        'notes', 'approved_by', 'created_by'
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'base_salary' => 'decimal:2',
    ];

    // Relationships
    public function employee(): BelongsTo
    public function approver(): BelongsTo
    public function creator(): BelongsTo

    // Scopes
    public function scopeEffectiveAt($query, $date)
    public function scopeCurrent($query)
}
```

### Employee Model Updates

Add to `Modules/HR/app/Models/Employee.php`:

```php
// New relationship
public function salaryHistory(): HasMany
{
    return $this->hasMany(EmployeeSalaryHistory::class)
                ->orderBy('effective_date', 'desc');
}

// Get salary at a specific date
public function getSalaryAt(Carbon $date): ?float
{
    return $this->salaryHistory()
        ->where('effective_date', '<=', $date)
        ->where(function($q) use ($date) {
            $q->whereNull('end_date')
              ->orWhere('end_date', '>=', $date);
        })
        ->first()
        ?->base_salary;
}

// Get current salary (from history, not base_salary field)
public function getCurrentSalary(): ?float
{
    return $this->salaryHistory()
        ->whereNull('end_date')
        ->first()
        ?->base_salary ?? $this->base_salary;
}
```

### Migration Strategy for Existing Data

```php
// Migration will:
// 1. Create employee_salary_history table
// 2. For each employee with base_salary > 0:
//    - Create initial history record with effective_date = hire_date or 2024-01-01
//    - Reason = 'initial'
// 3. Keep base_salary field for backwards compatibility (sync on update)
```

### UI Changes

**Employee Edit Form** (`Modules/HR/resources/views/employees/edit.blade.php`):
- Add "Salary History" tab/section
- Show timeline of salary changes
- "Add Salary Change" button opens modal:
  - New salary amount
  - Effective date
  - Reason dropdown
  - Notes field
  - Auto-closes previous salary record

**Employee Show Page**:
- Display current salary with "View History" link
- Show salary history timeline

---

## Phase 2: Payroll → Accounting Integration

### Business Rules

1. **When Payroll Run is created (status: pending)**:
   - Create `ExpenseSchedule` entry per employee
   - Status: `scheduled`
   - Category: "Payroll Expense" (auto-create if not exists)
   - Due date: Payroll period end date
   - Link to payroll run for drill-down

2. **When Payroll is marked as "Transferred/Paid"**:
   - Update expense status to `paid`
   - Set paid_date
   - Update cash flow actuals

3. **Expense Details**:
   - Click expense → See payroll breakdown
   - Employee name, base salary, deductions, net amount
   - Link to full payroll run

### Database Changes

**Modify `expense_schedules` table**:
```sql
ALTER TABLE expense_schedules ADD COLUMN payroll_run_id BIGINT NULL;
ALTER TABLE expense_schedules ADD COLUMN payroll_employee_id BIGINT NULL;
ALTER TABLE expense_schedules ADD FOREIGN KEY (payroll_run_id)
    REFERENCES payroll_runs(id) ON DELETE SET NULL;
```

**Modify `payroll_runs` table**:
```sql
ALTER TABLE payroll_runs ADD COLUMN transfer_status
    ENUM('pending', 'processing', 'transferred', 'failed') DEFAULT 'pending';
ALTER TABLE payroll_runs ADD COLUMN transferred_at TIMESTAMP NULL;
ALTER TABLE payroll_runs ADD COLUMN transferred_by BIGINT NULL;
```

### New Service: PayrollAccountingSyncService

**Location**: `app/Services/PayrollAccountingSyncService.php`

```php
class PayrollAccountingSyncService
{
    /**
     * Create scheduled expenses when payroll is finalized
     */
    public function createScheduledExpenses(PayrollRun $payrollRun): void
    {
        // Get or create "Payroll Expense" category
        $category = ExpenseCategory::firstOrCreate(
            ['slug' => 'payroll-expense'],
            ['name' => 'Payroll Expense', 'type' => 'operational']
        );

        // Create expense for each employee in payroll
        ExpenseSchedule::create([
            'expense_category_id' => $category->id,
            'description' => "Payroll - {$payrollRun->period_name}",
            'amount' => $payrollRun->total_net_salary,
            'currency' => 'EGP',
            'due_date' => $payrollRun->period_end_date,
            'status' => 'scheduled',
            'is_recurring' => false,
            'payroll_run_id' => $payrollRun->id,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Mark expenses as paid when payroll is transferred
     */
    public function markAsPaid(PayrollRun $payrollRun): void
    {
        ExpenseSchedule::where('payroll_run_id', $payrollRun->id)
            ->update([
                'status' => 'paid',
                'paid_date' => now(),
            ]);

        $payrollRun->update([
            'transfer_status' => 'transferred',
            'transferred_at' => now(),
            'transferred_by' => auth()->id(),
        ]);
    }
}
```

### Event Listeners

```php
// app/Listeners/PayrollRunFinalizedListener.php
class PayrollRunFinalizedListener
{
    public function handle(PayrollRunFinalized $event)
    {
        app(PayrollAccountingSyncService::class)
            ->createScheduledExpenses($event->payrollRun);
    }
}

// app/Listeners/PayrollTransferredListener.php
class PayrollTransferredListener
{
    public function handle(PayrollTransferred $event)
    {
        app(PayrollAccountingSyncService::class)
            ->markAsPaid($event->payrollRun);
    }
}
```

### UI Changes

**Payroll Run View**:
- Add "Transfer Status" badge
- Add "Mark as Transferred" button (opens confirmation modal)
- Show linked expense with "View in Accounting" link

**Expense Schedule View**:
- If expense has `payroll_run_id`:
  - Show "Payroll Details" section
  - Display employee breakdown
  - Link to full payroll run

---

## Phase 3: Project Costs → Accounting Sync

### Business Rules

1. **When ProjectCost is created**:
   - Auto-create `ExpenseSchedule` entry
   - Link via `project_cost_id`
   - Category mapped from cost_type
   - Include project reference in description

2. **When ProjectCost is updated/deleted**:
   - Sync changes to linked expense
   - Or prevent deletion if expense is paid

3. **Cost Type → Category Mapping**:
   - `labor` → "Project Labor Costs"
   - `expense` → "Project Expenses"
   - `contractor` → "Contractor Fees"
   - `infrastructure` → "Infrastructure Costs"
   - `software` → "Software & Licenses"
   - `other` → "Project - Other"

### Database Changes

**Modify `expense_schedules` table**:
```sql
ALTER TABLE expense_schedules ADD COLUMN project_cost_id BIGINT NULL;
ALTER TABLE expense_schedules ADD COLUMN project_id BIGINT NULL;
ALTER TABLE expense_schedules ADD FOREIGN KEY (project_cost_id)
    REFERENCES project_costs(id) ON DELETE SET NULL;
ALTER TABLE expense_schedules ADD FOREIGN KEY (project_id)
    REFERENCES projects(id) ON DELETE SET NULL;
```

### New Service: ProjectAccountingSyncService

**Location**: `app/Services/ProjectAccountingSyncService.php`

```php
class ProjectAccountingSyncService
{
    private array $categoryMapping = [
        'labor' => 'Project Labor Costs',
        'expense' => 'Project Expenses',
        'contractor' => 'Contractor Fees',
        'infrastructure' => 'Infrastructure Costs',
        'software' => 'Software & Licenses',
        'other' => 'Project - Other',
    ];

    public function syncCostToAccounting(ProjectCost $cost): ExpenseSchedule
    {
        $category = $this->getOrCreateCategory($cost->cost_type);

        return ExpenseSchedule::updateOrCreate(
            ['project_cost_id' => $cost->id],
            [
                'expense_category_id' => $category->id,
                'description' => "[{$cost->project->code}] {$cost->description}",
                'amount' => $cost->amount,
                'currency' => $cost->currency ?? 'EGP',
                'due_date' => $cost->cost_date,
                'status' => $cost->is_paid ? 'paid' : 'scheduled',
                'paid_date' => $cost->is_paid ? $cost->cost_date : null,
                'project_id' => $cost->project_id,
                'is_recurring' => false,
                'created_by' => $cost->created_by ?? auth()->id(),
            ]
        );
    }
}
```

---

## Phase 4: Labor Cost Persistence

### Business Rules

1. **Monthly Labor Cost Calculation**:
   - Run at month-end (or on-demand)
   - Calculate labor costs from Jira worklogs
   - Use salary effective at that time (from salary history)
   - Apply labor cost multiplier
   - Apply PM overhead (20%)
   - Store as `ProjectCost` with type `labor`

2. **Auto-sync to Accounting**:
   - ProjectCost creation triggers accounting sync
   - Creates scheduled expense for labor

### Update ProjectFinancialService

```php
public function persistLaborCosts(Project $project, Carbon $month): Collection
{
    $startDate = $month->copy()->startOfMonth();
    $endDate = $month->copy()->endOfMonth();

    $laborData = $this->calculateLaborCostsFromWorklogs($project, $startDate, $endDate);

    $costs = collect();

    foreach ($laborData['details'] as $detail) {
        // Use salary history for accurate calculation
        $employee = Employee::find($detail['employee_id']);
        $salaryAtTime = $employee->getSalaryAt($startDate);

        $cost = ProjectCost::updateOrCreate(
            [
                'project_id' => $project->id,
                'cost_type' => 'labor',
                'reference_type' => 'monthly_labor',
                'reference_id' => "{$detail['employee_id']}_{$month->format('Y-m')}",
            ],
            [
                'description' => "Labor: {$detail['employee_name']} - {$month->format('F Y')}",
                'amount' => $detail['total_cost'],
                'cost_date' => $endDate,
                'is_billable' => true,
                'metadata' => [
                    'hours' => $detail['total_hours'],
                    'hourly_rate' => $detail['hourly_rate'],
                    'salary_used' => $salaryAtTime,
                    'multiplier' => $this->getLaborCostMultiplier(),
                ],
                'created_by' => auth()->id(),
            ]
        );

        // Sync to accounting
        app(ProjectAccountingSyncService::class)->syncCostToAccounting($cost);

        $costs->push($cost);
    }

    return $costs;
}
```

---

## Phase 5: Contract → Project Revenue Auto-Sync

### Business Rules

1. **When Contract is linked to Project**:
   - For each `ContractPayment` milestone
   - Create corresponding `ProjectRevenue` entry
   - Link via `contract_payment_id`

2. **When ContractPayment status changes**:
   - Update linked ProjectRevenue status
   - Sync amounts if changed

### Database Changes

**Modify `project_revenues` table**:
```sql
ALTER TABLE project_revenues ADD COLUMN contract_payment_id BIGINT NULL;
ALTER TABLE project_revenues ADD FOREIGN KEY (contract_payment_id)
    REFERENCES contract_payments(id) ON DELETE SET NULL;
```

### Service: ContractProjectSyncService

```php
class ContractProjectSyncService
{
    public function syncContractToProjectRevenue(Contract $contract): void
    {
        // Get all projects linked to this contract
        $projects = $contract->projects;

        if ($projects->isEmpty()) {
            return;
        }

        foreach ($contract->payments as $payment) {
            // Split evenly across projects or use allocation percentage
            $amountPerProject = $payment->amount / $projects->count();

            foreach ($projects as $project) {
                ProjectRevenue::updateOrCreate(
                    ['contract_payment_id' => $payment->id, 'project_id' => $project->id],
                    [
                        'revenue_type' => 'contract',
                        'contract_id' => $contract->id,
                        'description' => $payment->description ?? "Contract Payment - {$payment->due_date}",
                        'amount_expected' => $amountPerProject,
                        'amount_received' => $payment->status === 'paid' ? $amountPerProject : 0,
                        'expected_date' => $payment->due_date,
                        'received_date' => $payment->paid_date,
                        'status' => $this->mapPaymentStatus($payment->status),
                    ]
                );
            }
        }
    }
}
```

---

## Phase 6: Time Estimates Enhancement

### New Features

1. **Team Member Time Estimates**:
   - Any project team member can add time estimates
   - Estimates linked to employee who created them
   - Optional: Assign to specific employee for execution

2. **Jira Integration**:
   - Pull estimates from Jira issue fields (original_estimate, remaining_estimate)
   - Option to sync periodically or on-demand
   - Map Jira issues to ProjectTimeEstimate

3. **Optional Link to Sales Estimates**:
   - Add `time_estimate_id` to `estimate_items`
   - When creating sales estimate line item, optionally select time estimate
   - Auto-calculate: hours × hourly_rate = amount
   - Show variance when time estimate actuals differ

### Database Changes

**Modify `project_time_estimates` table**:
```sql
ALTER TABLE project_time_estimates ADD COLUMN created_by BIGINT NULL;
ALTER TABLE project_time_estimates ADD COLUMN assigned_to BIGINT NULL;
ALTER TABLE project_time_estimates ADD COLUMN jira_issue_id BIGINT NULL;
ALTER TABLE project_time_estimates ADD COLUMN jira_issue_key VARCHAR(50) NULL;
ALTER TABLE project_time_estimates ADD COLUMN is_from_jira BOOLEAN DEFAULT FALSE;

ALTER TABLE project_time_estimates ADD FOREIGN KEY (created_by)
    REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE project_time_estimates ADD FOREIGN KEY (assigned_to)
    REFERENCES employees(id) ON DELETE SET NULL;
```

**Modify `estimate_items` table**:
```sql
ALTER TABLE estimate_items ADD COLUMN time_estimate_id BIGINT NULL;
ALTER TABLE estimate_items ADD COLUMN hourly_rate DECIMAL(10,2) NULL;
ALTER TABLE estimate_items ADD FOREIGN KEY (time_estimate_id)
    REFERENCES project_time_estimates(id) ON DELETE SET NULL;
```

### Jira Estimate Sync Service

```php
class JiraEstimateSyncService
{
    public function syncEstimatesFromJira(Project $project): int
    {
        $jiraIssues = JiraIssue::where('project_id', $project->id)
            ->whereNotNull('original_estimate_hours')
            ->get();

        $synced = 0;

        foreach ($jiraIssues as $issue) {
            ProjectTimeEstimate::updateOrCreate(
                ['jira_issue_key' => $issue->issue_key, 'project_id' => $project->id],
                [
                    'task_name' => $issue->summary,
                    'description' => $issue->description,
                    'estimated_hours' => $issue->original_estimate_hours,
                    'actual_hours' => $issue->time_spent_hours ?? 0,
                    'status' => $this->mapJiraStatus($issue->status),
                    'is_from_jira' => true,
                    'jira_issue_id' => $issue->id,
                    'created_by' => auth()->id(),
                ]
            );
            $synced++;
        }

        return $synced;
    }
}
```

---

## Implementation Schedule

| Phase | Description | Estimated Effort | Dependencies |
|-------|-------------|------------------|--------------|
| **1** | Salary History Tracking | 2-3 days | None |
| **2** | Payroll → Accounting | 3-4 days | Phase 1 |
| **3** | Project Costs → Accounting | 2-3 days | None |
| **4** | Labor Cost Persistence | 2-3 days | Phase 1, 3 |
| **5** | Contract → Project Revenue | 2 days | None |
| **6** | Time Estimates Enhancement | 3-4 days | None |

**Total Estimated Effort**: 14-19 days

### Suggested Order

1. **Phase 1** (Foundation) - Must be first
2. **Phase 3** (Project Costs → Accounting) - Can run parallel
3. **Phase 2** (Payroll → Accounting) - Needs Phase 1
4. **Phase 4** (Labor Cost Persistence) - Needs Phase 1 & 3
5. **Phase 5** (Contract → Revenue) - Independent
6. **Phase 6** (Time Estimates) - Independent

---

## Testing Strategy

### Unit Tests
- Salary history effective date queries
- Labor cost calculation with historical salaries
- Cost type → category mapping
- Payment status mapping

### Integration Tests
- PayrollRun creation → ExpenseSchedule created
- PayrollRun transfer → Expense marked paid
- ProjectCost creation → ExpenseSchedule created
- Contract link → ProjectRevenue created

### E2E Tests (Playwright)
- Create payroll run → Verify expense in accounting
- Add project cost → Verify in accounting dashboard
- Create time estimate → Link to sales estimate
- Salary change → Verify historical calculations correct

---

## Rollback Plan

Each phase will include:
1. Database migration with `down()` method
2. Feature flag to disable new functionality
3. Sync service can be disabled via config
4. Old behavior preserved until verified

---

## Questions Resolved

| Question | Answer | Implementation Impact |
|----------|--------|----------------------|
| Payroll expense timing | Scheduled on generation, actual on transfer | Two-stage expense workflow |
| Labor cost attribution | TBD - awaiting answer | Configurable in Phase 4 |
| Historical data | Start fresh | No migration of old payrolls |
| Estimate integration | Separate with optional linking | Phase 6 adds time_estimate_id |
| Salary tracking | Track changes over time | Phase 1 foundation |

---

## Open Question

**Labor Cost Attribution** - Still need clarification:

When Ahmed works 80 hours on Project A and 40 hours on Project B, how should his 30,000 EGP salary be allocated?

- **Option A**: By hours (50%/25%/25% including non-project time)
- **Option B**: Only billable projects (67%/33%)
- **Option C**: Configure per project/employee

Please confirm preferred approach.

---

*Document Version: 1.0 | Created: Dec 31, 2025*
