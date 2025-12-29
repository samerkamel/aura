# Perfex CRM Database Migration Guide

**Source**: `2025-12-29-14-27-16_backup.sql`
**System**: Perfex CRM (PHP-based CRM system)
**Database**: MySQL/MariaDB
**Date Analyzed**: December 29, 2025

---

## Table of Contents

1. [Overview](#overview)
2. [Record Counts Summary](#record-counts-summary)
3. [Core Business Tables](#core-business-tables)
   - [Customers & Contacts](#customers--contacts)
   - [Invoices & Payments](#invoices--payments)
   - [Proposals](#proposals)
   - [Contracts](#contracts)
   - [Projects](#projects)
   - [Tasks](#tasks)
   - [Leads](#leads)
   - [Expenses](#expenses)
4. [HR & Payroll Tables](#hr--payroll-tables)
   - [Staff](#staff)
   - [Departments](#departments)
   - [Job Positions](#job-positions)
   - [Timesheets](#timesheets)
   - [Payroll](#payroll)
5. [Reference Tables](#reference-tables)
6. [Key Relationships](#key-relationships)
7. [Migration Recommendations](#migration-recommendations)

---

## Overview

This backup contains **300+ tables** from a Perfex CRM installation. The database follows a naming convention of `tbl*` prefix for all tables. The system includes modules for:

- **CRM**: Customers, Leads, Contacts
- **Finance**: Invoices, Proposals, Contracts, Expenses, Credit Notes
- **Project Management**: Projects, Tasks, Milestones
- **HR/Payroll**: Staff, Timesheets, Leave Management, Payroll
- **Support**: Tickets, Knowledge Base

---

## Record Counts Summary

| Table | Records | Description |
|-------|---------|-------------|
| `tblclients` | ~180 | Customers/Companies |
| `tblcontacts` | ~99 | Customer contacts |
| `tblinvoices` | ~1,440 | Invoices |
| `tblinvoicepaymentrecords` | ~1,672 | Invoice payments |
| `tblproposals` | ~451 | Proposals/Quotes |
| `tblcontracts` | ~4 | Contracts |
| `tblprojects` | ~212 | Projects |
| `tbltasks` | ~4,965 | Tasks |
| `tbltaskstimers` | ~25,813 | Task time entries |
| `tblleads` | ~698 | Sales leads |
| `tblexpenses` | ~408 | Expenses |
| `tblstaff` | ~52 | Staff/Employees |
| `tblitemable` | ~5,942 | Invoice/Proposal line items |
| `tblcreditnotes` | ~5 | Credit notes |

---

## Core Business Tables

### Customers & Contacts

#### `tblclients` - Customers/Companies
Primary table for all customers (both companies and individuals).

| Column | Type | Description |
|--------|------|-------------|
| `userid` | int(11) | Primary Key |
| `company` | varchar(191) | Company name |
| `vat` | varchar(50) | VAT/Tax number |
| `phonenumber` | varchar(30) | Phone number |
| `country` | int(11) | FK to tblcountries |
| `city` | varchar(100) | City |
| `zip` | varchar(15) | Postal code |
| `state` | varchar(50) | State/Province |
| `address` | varchar(200) | Primary address |
| `website` | varchar(150) | Website URL |
| `datecreated` | datetime | Creation date |
| `active` | int(11) | Status flag |
| `leadid` | int(11) | Converted from lead ID |
| `billing_street` | varchar(200) | Billing address |
| `billing_city` | varchar(100) | Billing city |
| `billing_state` | varchar(100) | Billing state |
| `billing_zip` | varchar(100) | Billing postal code |
| `billing_country` | int(11) | Billing country |
| `shipping_street` | varchar(200) | Shipping address |
| `shipping_city` | varchar(100) | Shipping city |
| `shipping_state` | varchar(100) | Shipping state |
| `shipping_zip` | varchar(100) | Shipping postal code |
| `shipping_country` | int(11) | Shipping country |
| `default_language` | varchar(40) | Preferred language |
| `default_currency` | int(11) | Preferred currency |
| `show_primary_contact` | tinyint(1) | Display preference |
| `registration_confirmed` | int(11) | Registration status |
| `addedfrom` | int(11) | Created by staff ID |

**Migration Notes:**
- Maps to `customers` table in Aura
- `userid` → `id`
- `company` → `name`
- Currency lookup via `tblcurrencies`
- Country lookup via `tblcountries`

---

#### `tblcontacts` - Customer Contacts
Individual contacts associated with customer accounts.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary Key |
| `userid` | int(11) | FK to tblclients |
| `is_primary` | int(11) | Primary contact flag |
| `firstname` | varchar(191) | First name |
| `lastname` | varchar(191) | Last name |
| `email` | varchar(100) | Email address |
| `phonenumber` | varchar(100) | Phone number |
| `title` | varchar(200) | Job title |
| `datecreated` | datetime | Creation date |
| `active` | tinyint(1) | Status flag |
| `profile_image` | varchar(300) | Avatar path |
| `invoice_emails` | tinyint(1) | Receive invoice emails |
| `estimate_emails` | tinyint(1) | Receive estimate emails |
| `contract_emails` | tinyint(1) | Receive contract emails |
| `task_emails` | tinyint(1) | Receive task emails |
| `project_emails` | tinyint(1) | Receive project emails |

**Migration Notes:**
- Consider creating a `customer_contacts` table
- `userid` references `tblclients.userid`

---

### Invoices & Payments

#### `tblinvoices` - Invoices
Main invoice records.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary Key |
| `sent` | tinyint(1) | Sent status |
| `datesend` | datetime | Date sent |
| `clientid` | int(11) | FK to tblclients |
| `number` | int(11) | Invoice number sequence |
| `prefix` | varchar(50) | Invoice prefix (e.g., "INV-") |
| `datecreated` | datetime | Creation timestamp |
| `date` | date | Invoice date |
| `duedate` | date | Due date |
| `currency` | int(11) | FK to tblcurrencies |
| `subtotal` | decimal(11,2) | Subtotal before tax |
| `total_tax` | decimal(11,2) | Tax amount |
| `total` | decimal(11,2) | Total amount |
| `adjustment` | decimal(11,2) | Manual adjustment |
| `status` | int(11) | Status code |
| `clientnote` | text | Notes visible to client |
| `adminnote` | text | Internal notes |
| `discount_percent` | decimal(11,2) | Discount percentage |
| `discount_total` | decimal(11,2) | Discount amount |
| `discount_type` | varchar(30) | Discount type |
| `recurring` | int(11) | Recurring invoice flag |
| `recurring_type` | varchar(10) | Recurring frequency |
| `custom_recurring` | int(11) | Custom recurring flag |
| `project_id` | int(11) | Related project ID |
| `billing_street` | varchar(200) | Billing address |
| `billing_city` | varchar(100) | Billing city |
| `billing_state` | varchar(100) | Billing state |
| `billing_zip` | varchar(100) | Billing zip |
| `billing_country` | int(11) | Billing country |
| `hash` | varchar(32) | Unique hash for public URL |

**Invoice Status Codes:**
- `1` = Unpaid
- `2` = Paid
- `3` = Partially Paid
- `4` = Overdue
- `5` = Cancelled
- `6` = Draft

**Migration Notes:**
- Maps to `invoices` table in Aura (Accounting module)
- Line items in `tblitemable` with `rel_type='invoice'`
- Payment records in `tblinvoicepaymentrecords`

---

#### `tblinvoicepaymentrecords` - Invoice Payments
Payment records for invoices.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary Key |
| `invoiceid` | int(11) | FK to tblinvoices |
| `amount` | decimal(11,2) | Payment amount |
| `paymentmode` | varchar(40) | Payment method ID |
| `paymentmethod` | varchar(200) | Payment method name |
| `date` | date | Payment date |
| `daterecorded` | datetime | Record timestamp |
| `note` | text | Payment notes |
| `transactionid` | mediumtext | Transaction reference |

**Migration Notes:**
- Maps to `invoice_payments` or similar
- `paymentmode` references `tblpaymentmodes`

---

### Proposals

#### `tblproposals` - Proposals/Estimates
Quotes and proposals sent to clients.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary Key |
| `subject` | mediumtext | Proposal title/subject |
| `content` | longtext | HTML content |
| `addedfrom` | int(11) | Created by staff ID |
| `datecreated` | datetime | Creation date |
| `total` | decimal(15,2) | Total amount |
| `subtotal` | decimal(15,2) | Subtotal before tax |
| `total_tax` | decimal(15,2) | Tax amount |
| `discount_percent` | decimal(15,2) | Discount percentage |
| `discount_total` | decimal(15,2) | Discount amount |
| `discount_type` | varchar(30) | Discount type |
| `currency` | int(11) | FK to tblcurrencies |
| `open_till` | date | Valid until date |
| `date` | date | Proposal date |
| `rel_id` | int(11) | Related entity ID |
| `rel_type` | varchar(40) | Related entity type (customer/lead) |
| `assigned` | int(11) | Assigned staff ID |
| `project_id` | int(11) | Related project ID |
| `status` | int(11) | Status code |
| `estimate_id` | int(11) | Linked estimate ID |
| `invoice_id` | int(11) | Converted invoice ID |
| `hash` | varchar(32) | Unique hash |
| `email` | varchar(150) | Recipient email |
| `proposal_to` | varchar(191) | Recipient name |
| `country` | int(11) | Country |
| `zip` | varchar(50) | Postal code |
| `state` | varchar(100) | State |
| `city` | varchar(100) | City |
| `address` | varchar(200) | Address |
| `phone` | varchar(50) | Phone |

**Proposal Status Codes:**
- `1` = Draft
- `2` = Sent
- `3` = Open
- `4` = Revised
- `5` = Declined
- `6` = Accepted

**Migration Notes:**
- Maps to `estimates` table in Aura (Accounting module)
- Line items in `tblitemable` with `rel_type='proposal'`

---

### Contracts

#### `tblcontracts` - Contracts
Customer contracts and agreements.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary Key |
| `content` | longtext | Contract HTML content |
| `description` | text | Brief description |
| `subject` | varchar(300) | Contract title |
| `client` | int(11) | FK to tblclients |
| `datestart` | date | Start date |
| `dateend` | date | End date |
| `contract_type` | int(11) | FK to tblcontracts_types |
| `project_id` | int(11) | Related project |
| `addedfrom` | int(11) | Created by staff ID |
| `dateadded` | datetime | Creation timestamp |
| `contract_value` | decimal(11,2) | Contract value |
| `trash` | tinyint(1) | Deleted flag |
| `signed` | tinyint(1) | Signed status |
| `signature` | varchar(40) | Signature file |
| `marked_as_signed` | tinyint(1) | Manually marked signed |
| `acceptance_firstname` | varchar(50) | Signer first name |
| `acceptance_lastname` | varchar(50) | Signer last name |
| `acceptance_email` | varchar(100) | Signer email |
| `acceptance_date` | datetime | Signature date |
| `acceptance_ip` | varchar(40) | Signer IP |

**Contract Types (tblcontracts_types):**
- 1 = Digital Marketing
- 2 = Website Design
- 3 = Online Store
- 4 = Custom Solution
- 5 = Mobile App
- 6 = Product License

**Migration Notes:**
- Maps to `contracts` table in Aura (Accounting module)
- Simple structure, direct field mapping

---

### Projects

#### `tblprojects` - Projects
Project management records.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary Key |
| `name` | varchar(191) | Project name |
| `description` | text | Project description |
| `status` | int(11) | Status code |
| `clientid` | int(11) | FK to tblclients |
| `billing_type` | int(11) | Billing method |
| `start_date` | date | Start date |
| `deadline` | date | Due date |
| `project_created` | date | Creation date |
| `date_finished` | datetime | Completion date |
| `progress` | int(11) | Progress percentage (0-100) |
| `progress_from_tasks` | int(11) | Auto-calculate from tasks |
| `project_cost` | decimal(11,2) | Fixed project cost |
| `project_rate_per_hour` | decimal(11,2) | Hourly rate |
| `estimated_hours` | decimal(11,2) | Estimated hours |
| `addedfrom` | int(11) | Created by staff ID |
| `contact_notification` | int(11) | Contact notification flag |

**Project Status Codes:**
- 1 = Not Started
- 2 = In Progress
- 3 = On Hold
- 4 = Finished
- 5 = Cancelled
- 50 = Internal (custom status)

**Billing Types:**
- 1 = Fixed Rate
- 2 = Project Hours
- 3 = Task Hours

**Migration Notes:**
- Maps to `projects` table in Aura (Project module)
- Status mapping required
- Tasks linked via `tbltasks`

---

### Tasks

#### `tbltasks` - Tasks
Task and to-do items.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary Key |
| `name` | mediumtext | Task name/title |
| `description` | text | Task description |
| `priority` | int(11) | Priority level |
| `dateadded` | datetime | Creation date |
| `startdate` | date | Start date |
| `duedate` | date | Due date |
| `datefinished` | datetime | Completion date |
| `addedfrom` | int(11) | Created by staff ID |
| `status` | int(11) | Status code |
| `rel_id` | int(11) | Related entity ID |
| `rel_type` | varchar(30) | Related entity type |
| `is_public` | tinyint(1) | Public task flag |
| `billable` | tinyint(1) | Billable flag |
| `billed` | tinyint(1) | Already billed |
| `invoice_id` | int(11) | Linked invoice ID |
| `hourly_rate` | decimal(11,2) | Task hourly rate |
| `milestone` | int(11) | Milestone ID |
| `kanban_order` | int(11) | Kanban board order |
| `visible_to_client` | tinyint(1) | Client visible |

**Task Status Codes:**
- 1 = Not Started
- 2 = Awaiting Feedback
- 3 = Testing
- 4 = In Progress
- 5 = Complete

**Task Priority:**
- 1 = Low
- 2 = Medium
- 3 = High
- 4 = Urgent

**rel_type Values:**
- `project` - Project task
- `customer` - Customer-related task
- `lead` - Lead-related task
- `invoice` - Invoice-related task

---

#### `tbltaskstimers` - Task Time Tracking
Time entries for tasks.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary Key |
| `task_id` | int(11) | FK to tbltasks |
| `start_time` | varchar(64) | Unix timestamp start |
| `end_time` | varchar(64) | Unix timestamp end |
| `staff_id` | int(11) | FK to tblstaff |
| `hourly_rate` | decimal(11,2) | Hourly rate applied |
| `note` | text | Time entry notes |

**Migration Notes:**
- Time stored as Unix timestamps (seconds)
- Calculate duration: `end_time - start_time`
- Maps to billable hours tracking

---

### Leads

#### `tblleads` - Sales Leads
Sales pipeline and lead management.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary Key |
| `hash` | varchar(65) | Unique hash |
| `name` | varchar(300) | Contact name |
| `title` | varchar(100) | Job title |
| `company` | varchar(300) | Company name |
| `description` | text | Lead notes |
| `country` | int(11) | FK to tblcountries |
| `city` | varchar(100) | City |
| `state` | varchar(50) | State |
| `address` | varchar(100) | Address |
| `zip` | varchar(15) | Postal code |
| `assigned` | int(11) | Assigned staff ID |
| `dateadded` | datetime | Creation date |
| `status` | int(11) | FK to tblleads_status |
| `source` | int(11) | FK to tblleads_sources |
| `lastcontact` | datetime | Last contact date |
| `addedfrom` | int(11) | Created by staff ID |
| `email` | varchar(150) | Email address |
| `website` | varchar(150) | Website URL |
| `phonenumber` | varchar(50) | Phone number |
| `date_converted` | datetime | Conversion date |
| `lost` | tinyint(1) | Lost lead flag |
| `junk` | int(11) | Junk lead flag |
| `is_public` | tinyint(1) | Public lead flag |
| `client_id` | int(11) | Converted client ID |
| `lead_value` | decimal(15,2) | Estimated value |
| `vat` | varchar(50) | VAT number |

**Lead Status (tblleads_status):**
- 1 = Customer (converted)
- 2 = Discovery
- 3 = Prequalification
- 4 = Qualification
- 5 = Solution Design
- 6 = Evaluation
- 7 = Negotiation
- 8 = Decision

**Lead Sources (tblleads_sources):**
- 1 = Google
- 2 = Facebook
- 3 = Referral
- 4 = Partner Referral
- 5 = Team Lead
- 6 = Management Lead
- 7 = Website

---

### Expenses

#### `tblexpenses` - Expenses
Business expense records.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary Key |
| `category` | int(11) | FK to tblexpenses_categories |
| `currency` | int(11) | FK to tblcurrencies |
| `amount` | decimal(11,2) | Expense amount |
| `tax` | int(11) | Tax ID |
| `tax2` | int(11) | Secondary tax ID |
| `reference_no` | varchar(100) | Reference number |
| `note` | text | Expense notes |
| `expense_name` | varchar(500) | Expense description |
| `clientid` | int(11) | Billable to client |
| `project_id` | int(11) | Related project |
| `billable` | int(11) | Billable flag |
| `invoiceid` | int(11) | Billed on invoice |
| `paymentmode` | varchar(50) | Payment method |
| `date` | date | Expense date |
| `recurring` | int(11) | Recurring flag |
| `dateadded` | datetime | Creation date |
| `addedfrom` | int(11) | Created by staff ID |
| `vendor` | int(11) | Vendor ID |

**Expense Categories (tblexpenses_categories):**
- 1 = OpEx-Bills
- 2 = OpEx-Consumables
- 3 = OpEx-Office Supplies
- 4 = CapEx-Furniture
- 5 = CapEx-Electronics
- 6 = Sunk
- 7 = Payroll
- 8 = COS-Templates
- 9 = OpEx-Services
- 10 = COS-Ads
- 11 = COS-Facebook Ads

---

## HR & Payroll Tables

### Staff

#### `tblstaff` - Staff/Employees
Employee records (Perfex CRM users).

| Column | Type | Description |
|--------|------|-------------|
| `staffid` | int(11) | Primary Key |
| `email` | varchar(100) | Email address |
| `firstname` | varchar(50) | First name |
| `lastname` | varchar(50) | Last name |
| `phonenumber` | varchar(30) | Phone number |
| `datecreated` | datetime | Hire/creation date |
| `profile_image` | varchar(300) | Avatar path |
| `last_login` | datetime | Last login time |
| `admin` | int(11) | Admin flag |
| `role` | int(11) | Role ID |
| `active` | int(11) | Active status |
| `hourly_rate` | decimal(11,2) | Default hourly rate |
| `birthday` | date | Date of birth |
| `sex` | varchar(15) | Gender |
| `marital_status` | varchar(25) | Marital status |
| `identification` | varchar(100) | ID number |
| `home_town` | varchar(200) | Home address |
| `current_address` | varchar(200) | Current address |
| `job_position` | int(11) | FK to tblhr_job_position |
| `workplace` | int(11) | Workplace ID |
| `account_number` | varchar(50) | Bank account |
| `name_account` | varchar(50) | Bank account name |
| `issue_bank` | varchar(200) | Bank name |
| `staff_identifi` | varchar(25) | Employee code |

**Migration Notes:**
- Maps to `employees` table in Aura (HR module)
- `staffid` → `id`
- Consider legacy ID field for reference
- Department assignment in `tblstaff_departments`

---

### Departments

#### `tbldepartments` - Departments
Department definitions.

| Column | Type | Description |
|--------|------|-------------|
| `departmentid` | int(11) | Primary Key |
| `name` | varchar(100) | Department name |
| `manager_id` | int(11) | Manager staff ID |
| `parent_id` | int(11) | Parent department ID |
| `hidefromclient` | tinyint(1) | Hide from clients |

**Current Departments:**
- 1 = Systems
- 3 = HR
- 4 = Administration
- 5 = Graphics
- 6 = Accounts
- 7 = Sales
- 8 = Websites
- 9 = Custom
- 10 = Mobile
- 11 = QA

**Staff-Department Link (tblstaff_departments):**
| Column | Type | Description |
|--------|------|-------------|
| `staffdepartmentid` | int(11) | Primary Key |
| `staffid` | int(11) | FK to tblstaff |
| `departmentid` | int(11) | FK to tbldepartments |

---

### Job Positions

#### `tblhr_job_position` - Job Titles
Employee job positions/titles.

| Column | Type | Description |
|--------|------|-------------|
| `position_id` | int(11) | Primary Key |
| `position_name` | varchar(200) | Title name |
| `job_position_description` | text | Description |
| `position_code` | varchar(50) | Position code |
| `department_id` | text | Department IDs |

**Current Positions:**
- 1 = General Manager
- 2 = Financial Manager
- 3 = Business Development Manager
- 4 = Assistant
- 5 = Accountant
- 6 = Office Boy
- 7 = Senior PHP Developer
- 8 = PHP Developer
- 9 = Junior PHP Developer
- 10-12 = Software Tester levels
- 13-15 = Wordpress Developer levels
- 16-17 = Project Manager levels
- 18-20 = Account Manager levels

---

### Timesheets

#### `tbltimesheets_*` - Timesheet Tables
Multiple tables for leave and attendance management.

**`tbltimesheets_requisition_leave`** - Leave Requests

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary Key |
| `staff_id` | int(11) | FK to tblstaff |
| `subject` | varchar(100) | Leave subject |
| `start_time` | datetime | Leave start |
| `end_time` | datetime | Leave end |
| `reason` | text | Reason for leave |
| `status` | int(11) | 0=Created, 1=Approved, 2=Rejected |
| `type_of_leave` | int(11) | Leave type ID |
| `number_of_days` | float | Days requested |

**`tbltimesheets_day_off`** - Annual Leave Balances

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary Key |
| `staffid` | int(11) | FK to tblstaff |
| `year` | varchar(45) | Year |
| `total` | varchar(45) | Total days |
| `remain` | varchar(45) | Remaining days |
| `days_off` | float | Days taken |
| `type_of_leave` | varchar(200) | Leave type |

---

### Payroll

#### `tblhrp_*` - Payroll Tables
HR Profile/Payroll management tables.

**`tblhrp_payslip_details`** - Payslip Details

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary Key |
| `payslip_id` | int(11) | Payslip batch ID |
| `staff_id` | int(11) | FK to tblstaff |
| `month` | date | Payroll month |
| `pay_slip_number` | text | Payslip number |
| `payment_run_date` | date | Payment date |
| `employee_name` | text | Employee name |
| `dept_name` | text | Department name |
| `standard_workday` | decimal(15,2) | Standard hours |
| `actual_workday` | decimal(15,2) | Actual hours worked |
| `paid_leave` | decimal(15,2) | Paid leave hours |
| `unpaid_leave` | decimal(15,2) | Unpaid leave hours |
| `gross_pay` | decimal(15,2) | Gross salary |
| `total_deductions` | decimal(15,2) | Total deductions |
| `net_pay` | decimal(15,2) | Net salary |
| `total_insurance` | decimal(15,2) | Insurance amount |
| `salary_of_the_probationary_contract` | decimal(15,2) | Probation salary |
| `salary_of_the_formal_contract` | decimal(15,2) | Full salary |

**`tblhr_allowance_type`** - Allowance Types

| Column | Type | Description |
|--------|------|-------------|
| `type_id` | int(11) | Primary Key |
| `type_name` | varchar(200) | Allowance name |
| `allowance_val` | decimal(15,2) | Default value |
| `taxable` | tinyint(1) | Taxable flag |

**Current Allowances:**
- 1 = Phone Allowance (200 EGP, taxable)
- 2 = Internet Allowance (50 EGP, non-taxable)

---

## Reference Tables

### `tblcurrencies` - Currencies

| ID | Symbol | Name | Default |
|----|--------|------|---------|
| 1 | $ | USD | No |
| 2 | € | EUR | No |
| 3 | EGP | EGP | **Yes** |
| 4 | £ | GBP | No |
| 5 | QAR | QAR | No |

### `tblcountries` - Countries
Standard country list (250 records) with ISO codes, calling codes, and country TLDs.

### `tblitemable` - Line Items
Universal line items table for invoices, proposals, estimates, etc.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary Key |
| `rel_id` | int(11) | Parent record ID |
| `rel_type` | varchar(15) | Parent type |
| `description` | mediumtext | Item description |
| `long_description` | mediumtext | Detailed description |
| `qty` | decimal(11,2) | Quantity |
| `rate` | decimal(11,2) | Unit price |
| `unit` | varchar(40) | Unit of measure |
| `item_order` | int(11) | Sort order |

**rel_type Values:**
- `invoice` - Invoice line items
- `proposal` - Proposal line items
- `estimate` - Estimate line items
- `credit_note` - Credit note line items

### `tblitems` - Products/Services Catalog
Reusable products and services.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary Key |
| `description` | mediumtext | Item name |
| `long_description` | text | Full description |
| `rate` | decimal(11,2) | Default price |
| `tax` | int(11) | Default tax |
| `unit` | varchar(40) | Unit of measure |
| `group_id` | int(11) | Category ID |
| `commodity_code` | varchar(100) | SKU/Item code |
| `active` | int(11) | Active flag |

---

## Key Relationships

### Entity Relationship Diagram (Simplified)

```
tblclients (customers)
├── tblcontacts (customer contacts) [userid → userid]
├── tblinvoices (invoices) [clientid → userid]
│   ├── tblinvoicepaymentrecords (payments) [invoiceid → id]
│   └── tblitemable (line items) [rel_id → id, rel_type='invoice']
├── tblproposals (proposals) [rel_id → userid, rel_type='customer']
│   └── tblitemable (line items) [rel_id → id, rel_type='proposal']
├── tblcontracts (contracts) [client → userid]
├── tblprojects (projects) [clientid → userid]
│   └── tbltasks (tasks) [rel_id → id, rel_type='project']
│       └── tbltaskstimers (time entries) [task_id → id]
├── tblexpenses (expenses) [clientid → userid]
└── tblleads (leads) [client_id → userid when converted]

tblstaff (employees)
├── tblstaff_departments (dept assignment) [staffid → staffid]
├── tbltaskstimers (time entries) [staff_id → staffid]
├── tblhrp_payslip_details (payroll) [staff_id → staffid]
└── tbltimesheets_requisition_leave (leave) [staff_id → staffid]
```

---

## Migration Recommendations

### Phase 1: Reference Data
1. **Currencies** - Import `tblcurrencies` to settings
2. **Countries** - Verify `tblcountries` matches Aura format
3. **Departments** - Import `tbldepartments`
4. **Job Positions** - Import `tblhr_job_position`

### Phase 2: Core Master Data
1. **Customers** - Migrate `tblclients` → `customers`
   - Map currency IDs
   - Map country IDs
   - Import contacts from `tblcontacts`

2. **Staff/Employees** - Migrate `tblstaff` → `employees`
   - Create department assignments from `tblstaff_departments`
   - Map job positions
   - Import bank details

### Phase 3: Financial Data
1. **Products/Services** - Migrate `tblitems` → products catalog
2. **Proposals** - Migrate `tblproposals` → `estimates`
   - Include line items from `tblitemable`
3. **Invoices** - Migrate `tblinvoices` → `invoices`
   - Include line items from `tblitemable`
   - Include payments from `tblinvoicepaymentrecords`
4. **Contracts** - Migrate `tblcontracts` → `contracts`
5. **Credit Notes** - Migrate `tblcreditnotes`
6. **Expenses** - Migrate `tblexpenses`

### Phase 4: Project Data
1. **Projects** - Migrate `tblprojects` → `projects`
2. **Tasks** - Migrate `tbltasks` related to projects
3. **Time Entries** - Migrate `tbltaskstimers` for billable hours

### Phase 5: HR Data
1. **Leave Balances** - Migrate `tbltimesheets_day_off`
2. **Leave Requests** - Migrate `tbltimesheets_requisition_leave`
3. **Payroll History** - Consider `tblhrp_payslip_details` for historical reference

### Phase 6: Leads (Optional)
1. **Leads** - Migrate `tblleads` if lead management needed
   - Map status codes
   - Map source codes

### Data Mapping Considerations

1. **ID Mapping**: Create a mapping table to track old Perfex IDs to new Aura IDs for reference integrity.

2. **Status Codes**: Perfex uses numeric status codes - create translation maps for each entity.

3. **Currency**: Default currency is EGP (id=3) - verify this matches Aura setup.

4. **Timestamps**: Some tables use Unix timestamps (e.g., tbltaskstimers.start_time) - convert to datetime.

5. **Files/Attachments**: File paths reference Perfex storage structure - may need re-upload.

6. **Custom Fields**: Perfex supports custom fields via `tblcustomfieldsvalues` - evaluate migration needs.

### Recommended Migration Scripts

```php
// Example: Customer Migration Query
SELECT
    userid as id,
    company as name,
    vat as tax_id,
    phonenumber as phone,
    email,
    website,
    address,
    city,
    state,
    zip as postal_code,
    (SELECT short_name FROM tblcountries WHERE country_id = c.country) as country_code,
    active as is_active,
    datecreated as created_at
FROM tblclients c
WHERE active = 1;
```

```php
// Example: Invoice Migration Query
SELECT
    i.id,
    i.number as invoice_number,
    CONCAT(i.prefix, i.number) as formatted_number,
    i.clientid as customer_id,
    i.date as invoice_date,
    i.duedate as due_date,
    (SELECT name FROM tblcurrencies WHERE id = i.currency) as currency,
    i.subtotal,
    i.total_tax as tax_amount,
    i.total,
    i.status,
    i.datecreated as created_at
FROM tblinvoices i;
```

---

## Additional Notes

### Tables Not Documented (Low Priority)
- Activity logs and audit trails
- Email templates and queues
- Ticket/Support system tables
- Knowledge base tables
- Warehouse/Inventory tables (tblwh_*)
- Custom field tables

### Data Quality Considerations
1. Some records may have `deleted_customer_name` indicating soft-deleted customers
2. Verify all foreign key references resolve
3. Check for orphaned line items in `tblitemable`
4. Validate date ranges (some projects date back to 2015)

---

**Document Version**: 1.0
**Last Updated**: December 29, 2025
**Author**: Auto-generated migration analysis
