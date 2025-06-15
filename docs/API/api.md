# API Documentation

This document provides detailed information about all API endpoints in the QFlow system.

## Navigation & Menu APIs

### Menu Configuration

The QFlow system provides menu configuration through static JSON files and does not expose dynamic menu APIs. The vertical menu structure is managed through:

- **Menu Configuration File**: `resources/menu/verticalMenu.json`
- **Menu Service Provider**: `app/Providers/MenuServiceProvider.php`
- **Menu Template**: `resources/views/layouts/sections/menu/verticalMenu.blade.php`

#### Menu Structure

The vertical menu follows a hierarchical structure with the following levels:

1. **Menu Headers** - Section dividers (e.g., "HR Management", "Payroll")
2. **Main Menu Items** - Primary navigation items with icons
3. **Submenu Items** - Secondary navigation under main items
4. **Deep Submenu Items** - Tertiary navigation (limited
   // ...
   ]
   ]

````

**CSV Import Request:**

```php
[
    'csv_file' => UploadedFile // CSV with EmployeeID,BillableHours columns
]
````

**Response Format (Manual Entry):**

- Success: Redirect with session flash message
- Error: Redirect with validation errors

**Response Format (CSV Import):**

- Success: Import summary view with processed records count
- Error: Redirect with import errors and failed records

#### Expected CSV Structure:

```csv
EmployeeID,BillableHours
1,40.5
2,35.0
3,42.25
```

#### Validation Rules:

**Manual Entry:**

- `hours.*`: numeric, min:0, max:999.99
- Employee must exist and be active

**CSV Import:**

- File must be CSV format
- Required headers: EmployeeID, BillableHours
- EmployeeID must exist in database
- BillableHours must be numeric (0-999.99)

#### Error Responses:

**Manual Entry Errors:**

```php
[
    'hours.1' => ['The hours field must be a number.'],
    'hours.2' => ['The selected employee is invalid.']
]
```

**CSV Import Errors:**

```php
[
    'csv_file' => ['The uploaded file must be a CSV.'],
    'import_errors' => [
        'Employee ID 999 not found',
        'Invalid hours value for Employee ID 2'
    ],
    'failed_rows' => [
        ['EmployeeID' => '999', 'BillableHours' => '40.5'],
        ['EmployeeID' => '2', 'BillableHours' => 'invalid']
    ]
]
```

## Future API Considerations

The billable hours functionality could be extended with dedicated REST API endpoints in the future:

- `GET /api/payroll/billable-hours` - List billable hours for current period
- `POST /api/payroll/billable-hours` - Create/update billable hours
- `GET /api/payroll/billable-hours/{employee}` - Get specific employee's hours
- `PUT /api/payroll/billable-hours/{employee}` - Update specific employee's hours

## Change Log

- 2025-06-14: Added billable hours API documentation for Story 3.4
