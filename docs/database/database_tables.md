# Database Tables Documentation

This document provides detailed information about all database tables in the QFlow system.

## Payroll Module Tables

### billable_hours

Stores monthly billable hours for each employee for payroll calculations.

**Columns:**

- `id` (BIGINT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT) - Unique identifier
- `employee_id` (BIGINT UNSIGNED, NOT NULL, FOREIGN KEY) - References employees.id
- `payroll_period_start_date` (DATE, NOT NULL) - Start date of the payroll period
- `hours` (DECIMAL(5,2), NOT NULL) - Number of billable hours for the period
- `created_at` (TIMESTAMP) - Record creation timestamp
- `updated_at` (TIMESTAMP) - Record last update timestamp

**Constraints:**

- Foreign Key: `employee_id` references `employees(id)` ON DELETE CASCADE
- Unique Key: `unique_employee_period` on (`employee_id`, `payroll_period_start_date`)

**Indexes:**

- Primary key on `id`
- Unique index on (`employee_id`, `payroll_period_start_date`)

**Usage:**

- Stores billable hours data entered manually or imported via CSV
- Used as a component in payroll calculations
- Supports upsert operations for updating existing periods
- One record per employee per payroll period

**Migration:** `2025_06_14_185048_create_billable_hours_table.php`

**Model:** `Modules\Payroll\app\Models\BillableHour`

**Related Models:**

- `Modules\HR\app\Models\Employee` (belongsTo relationship)

## HR Module Tables

### employees

Core employee information table.

**Relationships:**

- `billable_hours` - hasMany relationship to BillableHour model
- (Other relationships as defined in existing documentation)

## Change Log

- 2025-06-14: Added billable_hours table documentation for Story 3.4
