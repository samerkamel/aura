# Financial Data Color Scheme

This document defines the standardized color scheme for financial data throughout the Aura ERP system. These colors should be consistently applied across all modules and views where financial metrics are displayed.

## Color Standards

### 1. Balance
- **Color**: Red (`#dc3545` / Bootstrap `text-danger`)
- **Usage**: Account balances, cash flow balances, cumulative totals
- **Icon**: `ti-wallet`
- **Rationale**: Red represents cash outflow or current balance status

### 2. Contracts
- **Color**: Blue (`#0d6efd` / Bootstrap `text-primary`)
- **Usage**: Approved/active contracts, confirmed contract values
- **Icon**: `ti-file-check`
- **Rationale**: Blue represents solid, confirmed business agreements

### 3. Expected Contracts (Ex. Contracts)
- **Color**: Orange (`#fd7e14` / Bootstrap `text-warning`)
- **Usage**: Draft contracts, pending contracts, potential contract values
- **Icon**: `ti-file-clock`
- **Rationale**: Orange represents pending or uncertain status

### 4. Income
- **Color**: Green (`#198754` / Bootstrap `text-success`)
- **Usage**: Received payments, actual income, completed transactions
- **Icon**: `ti-currency-dollar`
- **Rationale**: Green represents positive cash flow and completed income

### 5. Expected Income (Ex. Income)
- **Color**: Purple (`#6f42c1`)
- **Usage**: Pending payments, overdue payments, expected but not received income
- **Icon**: `ti-hourglass`
- **Rationale**: Purple represents anticipated but not yet realized income

## Implementation Guidelines

### CSS Classes
```css
.balance-color { color: #dc3545; }       /* Red */
.contract-color { color: #0d6efd; }      /* Blue */
.expected-contract-color { color: #fd7e14; } /* Orange */
.income-color { color: #198754; }        /* Green */
.expected-income-color { color: #6f42c1; } /* Purple */
```

### Bootstrap Classes
- **Balance**: `text-danger`
- **Contracts**: `text-primary`
- **Expected Contracts**: `text-warning`
- **Income**: `text-success`
- **Expected Income**: Custom purple `style="color: #6f42c1;"`

### Icons
- **Balance**: `ti ti-wallet`
- **Contracts**: `ti ti-file-check`
- **Expected Contracts**: `ti ti-file-clock`
- **Income**: `ti ti-currency-dollar`
- **Expected Income**: `ti ti-hourglass`

## Usage Examples

### Income Sheet
- Applied in monthly breakdown tables
- Used in totals sections
- Consistent across all business unit rows

### Dashboard Cards
- Summary cards should use these colors
- Charts and graphs should maintain consistency
- KPI indicators should follow this scheme

### Reports
- Financial reports should use these colors
- Export formats should maintain color coding where possible
- Print versions should include color legends

## Consistency Rules

1. **Always use the same color for the same financial metric** across all views
2. **Icons should accompany colors** for accessibility and clarity
3. **Maintain contrast ratios** for accessibility compliance
4. **Document any deviations** and get approval before implementing different colors
5. **Test in both light and dark modes** to ensure visibility

## Related Views

This color scheme is implemented in:
- Income Sheet (`/accounting/income-sheet`)
- Accounting Dashboard
- Contract Management
- Financial Reports

## Last Updated
Date: {{ date('Y-m-d') }}
Version: 1.0