# Routes Documentation

This document provides detailed information about all routes in the QFlow system.

## Navigation Menu Structure

The QFlow system uses a comprehensive vertical menu structure organized into logical sections for HR management functionality. The menu is defined in `resources/menu/verticalMenu.json` and follows the Vuexy admin template structure.

### Menu Organization

#### 1. Dashboard Section

- **Analytics Dashboard** (`/`) - Main system dashboard with overview statistics
- **CRM Overview** (`/dashboard/crm`) - Customer relationship management dashboard

#### 2. HR Management Section

- **Employees** (`/hr/employees`) - Employee management with CRUD operations
  - All Employees listing
  - Add new employee form
  - Employee profile management
  - Document management
  - Off-boarding processes

#### 3. Attendance & Time Section

- **Attendance Records** (`/attendances`) - Time tracking and attendance management
  - View all attendance records
  - Add manual attendance entries
  - CSV import functionality
- **Attendance Settings** - Configuration and rules management
  - General attendance settings
  - Attendance rules configuration
  - Public holidays management

#### 4. Leave Management Section

- **Leave Requests** (`/leaves`) - Employee leave request management
  - View all leave requests
  - Create new leave requests
  - Approve/reject workflows
- **Leave Policies** (`/leave-policies`) - Leave policy configuration
  - PTO policy management
  - Sick leave policy settings

#### 5. Payroll Section

- **Payroll Management** (`/payrolls`) - Payroll processing and records
  - Payroll records management
  - Create payroll entries
  - Run and review payroll
- **Billable Hours** (`/payroll/billable-hours`) - Time tracking for billing
- **Payroll Settings** (`/payroll/settings`) - Payroll configuration

#### 6. Asset Management Section

- **Assets** (`/assetmanagers`) - Company asset tracking
  - All assets listing
  - Add new assets
  - Asset assignment tracking

#### 7. Administration Section

- **User Management** - System user administration
- **Roles & Permissions** - Access control management

#### 8. System Tools Section

- **Calendar** - System calendar integration
- **Email** - Email management
- **Account Settings** - User account configuration

#### 9. Development Tools Section

- **UI Components** - Development and testing tools
- **Tables & Forms** - Component testing

### Menu Configuration

The menu structure supports:

- **Hierarchical navigation** with nested submenus
- **Role-based access control** (planned integration)
- **Dynamic menu items** based on user permissions
- **Responsive design** following Vuexy template standards
- **Icon integration** using Tabler Icons

### Navigation Best Practices

1. **Logical Grouping**: Related functionality is grouped under appropriate menu headers
2. **Consistent Naming**: Menu items use clear, descriptive names
3. **Proper Hierarchy**: Three-level maximum depth for optimal usability
4. **Icon Consistency**: Meaningful icons that represent the functionality
5. **Route Alignment**: Menu URLs match the actual route definitions

## Payroll Module Routes

### Billable Hours Management

**Base URI:** `/payroll/billable-hours`

**Route Group:** `payroll.billable-hours`

**Middleware:** `web`, `auth` (authentication required)

#### Available Routes:

| Method | URI                       | Name                           | Controller@Action               | Description                                              |
| ------ | ------------------------- | ------------------------------ | ------------------------------- | -------------------------------------------------------- |
| GET    | `/payroll/billable-hours` | `payroll.billable-hours.index` | `BillableHoursController@index` | Display billable hours management page                   |
| POST   | `/payroll/billable-hours` | `payroll.billable-hours.store` | `BillableHoursController@store` | Store/update billable hours (manual entry or CSV import) |

#### Route Details:

**GET /payroll/billable-hours**

- **Purpose:** Display the billable hours management interface
- **Authentication:** Required
- **Returns:** Blade view with employee list and current period billable hours
- **View:** `Modules/Payroll/resources/views/billable-hours/index.blade.php`

**POST /payroll/billable-hours**

- **Purpose:** Handle both manual entry and CSV import of billable hours
- **Authentication:** Required
- **Request Types:**
  - Manual Entry: `hours[employee_id]` array with hours values
  - CSV Import: `csv_file` with EmployeeID and BillableHours columns
- **Returns:**
  - Manual Entry: Redirect back with success/error messages
  - CSV Import: Import summary view or redirect with errors
- **Validation:** Employee existence, hours format, CSV structure
- **View (CSV Success):** `Modules/Payroll/resources/views/billable-hours/import-summary.blade.php`

#### Usage Examples:

**Manual Entry Form:**

```html
<form method="POST" action="{{ route('payroll.billable-hours.store') }}">
  @csrf
  <input name="hours[1]" value="40.5" />
  <!-- Employee ID 1 -->
  <input name="hours[2]" value="35.0" />
  <!-- Employee ID 2 -->
  <button type="submit">Save All Changes</button>
</form>
```

**CSV Import Form:**

```html
<form method="POST" action="{{ route('payroll.billable-hours.store') }}" enctype="multipart/form-data">
  @csrf
  <input type="file" name="csv_file" accept=".csv" />
  <button type="submit">Import from CSV</button>
</form>
```

#### Expected CSV Format:

```csv
EmployeeID,BillableHours
1,40.5
2,35.0
3,42.25
```

## Module Integration

### Payroll Module

- **File:** `Modules/Payroll/routes/web.php`
- **Namespace:** `Modules\Payroll\app\Http\Controllers`
- **Controller:** `BillableHoursController`

## Change Log

- 2025-06-14: Added billable hours routes documentation for Story 3.4
