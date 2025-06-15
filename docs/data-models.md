The following schemas represent the core tables in the MySQL database. Primary keys are assumed to be auto-incrementing integers or UUIDs.

```SQL
-- Employee core information
CREATE TABLE `employees` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) UNIQUE,
  `position` VARCHAR(255),
  `start_date` DATE,
  `contact_info` JSON, -- Store phone, address etc.
  `bank_info` JSON, -- Store bank name, account number, etc. (encrypted)
  `base_salary` DECIMAL(10, 2),
  `status` ENUM('active', 'terminated', 'resigned') DEFAULT 'active',
  `termination_date` DATE NULL,
  `created_at` TIMESTAMP,
  `updated_at` TIMESTAMP
);

-- Employee documents with optional expiry dates
CREATE TABLE `employee_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `employee_id` BIGINT UNSIGNED NOT NULL,
  `document_type` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `issue_date` DATE NULL,
  `expiry_date` DATE NULL,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
);

-- Store complex attendance rules as structured data
CREATE TABLE `attendance_rules` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `rule_name` VARCHAR(255) NOT NULL,
    `rule_type` ENUM('flexible_hours', 'late_penalty', 'permission', 'wfh_policy'),
    `config` JSON NOT NULL -- e.g., {"from": "08:00", "to": "10:00", "required_hours": 8} or {"late_minutes": 30, "penalty_minutes": 60}
);

-- Stores raw attendance logs imported from CSV
CREATE TABLE `attendance_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `employee_id` BIGINT UNSIGNED NOT NULL,
    `timestamp` DATETIME NOT NULL,
    `type` ENUM('sign_in', 'sign_out')
);

-- Stores the results of each finalized payroll run for historical purposes
CREATE TABLE `payroll_runs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `employee_id` BIGINT UNSIGNED NOT NULL,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `base_salary` DECIMAL(10, 2),
    `final_salary` DECIMAL(10, 2),
    `calculation_data` JSON, -- Store all factors used in calculation (attendance_pct, billable_pct, etc.)
    `created_at` TIMESTAMP
);

-- Stores monthly billable hours for each employee for payroll calculations
CREATE TABLE `billable_hours` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `employee_id` BIGINT UNSIGNED NOT NULL,
    `payroll_period_start_date` DATE NOT NULL,
    `hours` DECIMAL(5, 2) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_employee_period` (`employee_id`, `payroll_period_start_date`)
);

-- Note: The data retention strategy will involve moving records older than a certain threshold
-- from `payroll_runs` and `attendance_logs` to corresponding `_history` tables.
```
