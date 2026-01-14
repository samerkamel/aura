# Budget Report Feature Requirements

## Overview
New report page under **Financial Planning > Reports > Budget**

## Location
- Menu: Financial Planning → Reports → Budget
- Route: TBD
- Module: Accounting

## Purpose
- **View** budgets for any financial year
- **Create** budgets at the beginning of each financial year (or a month before it starts)
- Mechanism to compensate for missing month data (to be detailed)

## Requirements

### Functional Requirements
(Awaiting user input)

### Data Sources
- **Products/Services**: From existing Products in the system (not a fixed list)
  - Include inactive products if they had income in previous years
- **Revenue/Income**:
  - Invoices
  - Payments without invoices (direct contract payments)

---

## Growth Section

### Historical Data Table
- Shows last 3 years of income data per product
- Products that existed in past years but are now inactive should still appear
- Data source: Invoices + payments without invoices

### Growth Chart (per product)
- **Type**: Bar chart
- **Data shown**: Last 3 years + the budgeting year
- **Trendline options**:
  - Linear
  - Logarithmic
  - Polynomial (with selectable orders)
- **Purpose**: Helps determine budgeted income value based on growth trend

### Budget Entry
- User enters budgeted income per product based on trendline analysis
- Growth method selection per product

---

## Capacity Section

### Scope
- Only employees with **developer status** (those who log hours)
- Calculated per product

### Last Financial Year Table (Read-only calculations)
| Column | Description |
|--------|-------------|
| Headcount | From employee data, per product |
| Available Hours | (5 hours/day × work days excluding holidays & weekends) / 12 months |
| Avg Hourly Price | Average hourly rate for employees assigned to this product |
| Income | Actual income from last year |
| Billable Hours/Employee/Month | Income ÷ (Headcount × Avg Price) |
| Billable % | Billable Hours ÷ Available Hours |

### Next Year Budget Table (Editable)
| Column | Description |
|--------|-------------|
| Headcount | Clickable - allows planning new hires with target month |
| Avg Hourly Price | User input - average rate per employee |
| Billable % | User input - expected billable percentage |
| Budgeted Income | **Calculated**: Available Hours × Headcount × Avg Price × Billable % |

### Hiring Planning
- Click on headcount to open hiring plan modal
- Set number of new hires per product
- Set target hire month for each
- System calculates weighted average headcount based on months active

---

## Collection Section

### Purpose
Calculate target income based on collection timing patterns

### Last Year Analysis (per product)
| Column | Source/Calculation |
|--------|-------------------|
| Beginning Balance | Outstanding balance at start of last year |
| End Balance | Outstanding balance at end of last year |
| Average Balance | (Beginning + End) / 2 |
| Avg Contract/Month | From Income sheet |
| Avg Payment/Month | From Income sheet |
| Collection Months | Average Balance ÷ Avg Payment/Month |

**Example**: Collection months = 1.7

### Budgeted Year Payment Schedule Table (Editable, per product)
- Define expected payment schedule patterns
- Set percentage of contracts following each pattern
- Example for PHP:
  - 20% of contracts: 60% in Month 1, 40% in Month 2

| Pattern | % of Contracts | M1 | M2 | M3 | ... |
|---------|---------------|----|----|----| --- |
| Pattern A | 20% | 60% | 40% | 0% | ... |
| Pattern B | 50% | 100% | 0% | 0% | ... |
| Pattern C | 30% | 33% | 33% | 34% | ... |

### Collection Months Calculation
1. Calculate budgeted year collection months from payment schedule table (e.g., 2.36)
2. Average with last year: (1.7 + 2.36) / 2 = 2.02
3. This becomes the projected collection months

### Target Income Calculation
```
Avg Collection/Month = End Balance (last year) ÷ Projected Collection Months
Target Income = Avg Collection/Month × 12

Example:
End Balance = 853,405
Collection Months = 2.02
Avg Collection = 853,405 / 2.02 = 422,855.31
Target Income = 422,855.31 × 12 = 5,074,263.76
```

---

## Result Section

### Purpose
Consolidate all three budgeting methods and select final budget per product

### Table Structure (per product row)
| Column | Description |
|--------|-------------|
| Product | Product name |
| Growth | Budget value from Growth method (trendline) |
| Capacity | Budget value from Capacity method |
| Collection | Budget value from Collection method |
| Average | (Growth + Capacity + Collection) / 3 |
| **Final** | **User input** - selected budget value |

### Functionality
- All three method values shown side-by-side for comparison
- Average calculated automatically
- User enters final selected value (can be one of the methods, the average, or a custom value)

---

## Personnel Section

### Purpose
Plan salary increases and new hires across all products/departments

### Employee Allocation
- Employees can be allocated to **multiple products** with a percentage split
- Employee appears in **all sections** they are allocated to
- **G&A (General & Administrative)** section for non-product employees:
  - Office staff, accountants, HR, etc.
  - Cannot be assigned to a product

### Table Structure (per product/G&A section)
| Column | Description |
|--------|-------------|
| Employee Name | Employee name |
| Current Salary | Current monthly/annual salary |
| Proposed Salary | **User input** - new salary |
| Increase % | **Calculated**: (Proposed - Current) / Current × 100 |
| Allocation % | Percentage allocated to this product (for split employees) |
| Effective Cost | Proposed Salary × Allocation % |

### New Hires
- Employees added in **Capacity** section (hiring plan) appear here automatically
- New hires added here appear in **Capacity** section automatically
- Bidirectional sync between Personnel and Capacity

### Sections
1. **Per Product sections** - employees allocated to each product
2. **G&A section** - general/administrative employees

### Totals
- Total salary cost per product
- Total salary cost for G&A
- Grand total for all personnel

---

## OpEx (Operating Expenses) Section

### Purpose
Budget all operating expenses (non-CapEx, non-Tax categories)

### Data Source
- All expense categories from the system (excluding CapEx and Tax categories)
- Show **all categories** even if no payments in last year (for planning purposes)

### Table Structure
| Column | Description |
|--------|-------------|
| Category | Expense category name |
| Last Year Avg/Month | Average monthly expense from current/last year |
| Last Year Total | Total expense from current/last year |
| Increase % | **User input** - defaults to global increase (e.g., 10%) |
| Proposed Avg/Month | **Calculated or Override** - can input exact value |
| Proposed Year Total | Proposed Avg × 12 |

### Global Increase Setting
- Set a **global default increase %** (e.g., 10%) that applies to all categories
- Individual categories can **override** with:
  - Custom percentage increase
  - Exact proposed amount

### Functionality
- Categories with 0 expenses last year still shown for planning
- Example: Transportation may have 0 last year but needs budget for next year

### Totals
- Total monthly OpEx budget
- Total yearly OpEx budget

---

## Taxes Section

### Purpose
Budget all tax-related expenses

### Structure
Same as OpEx section but for **Tax categories only**

### Table Structure
| Column | Description |
|--------|-------------|
| Category | Tax category name |
| Last Year Avg/Month | Average monthly tax from current/last year |
| Last Year Total | Total tax from current/last year |
| Increase % | **User input** - defaults to global increase |
| Proposed Avg/Month | **Calculated or Override** |
| Proposed Year Total | Proposed Avg × 12 |

### Global Increase Setting
- Separate global default increase % for taxes
- Individual categories can override

### Totals
- Total monthly tax budget
- Total yearly tax budget

---

## CapEx (Capital Expenditure) Section

### Purpose
Budget capital expenditure (equipment, software licenses, major purchases)

### Structure
Similar to OpEx/Taxes sections but for **CapEx categories**

### Table Structure
| Column | Description |
|--------|-------------|
| Category | CapEx category name |
| Current Year Total | Total CapEx from current year |
| Proposed Year Total | **User input** - budgeted amount |

### Functionality
- Show all CapEx categories
- Display current year spending
- Allow input for budgeted year amounts

### Totals
- Total yearly CapEx budget

---

## P&L (Profit & Loss) Section

### Purpose
Summary view consolidating all budget data into standard P&L format

### Structure (Read-only, calculated from other sections)
```
Revenue
├── Product 1 (from Result - Final values)
├── Product 2
├── ...
└── Total Sales

Cost of Sales (from OpEx - specific category)
VAT (from Taxes - specific category)

= Gross Profit

Other Direct Expenses
├── Salaries (from Personnel - product allocations)
├── Sales Commissions
└── = Earnings

Contribution
├── G&A Salaries (from Personnel - G&A section)
├── OpEx (from OpEx section - excluding Cost of Sales)
├── Taxes (from Taxes section - excluding VAT)
└── = Profit

CapEx (from CapEx section total)
```

### Category Mapping
- **Cost of Sales**: OpEx category
- **VAT**: Tax category
- **Sales Commissions**: OpEx category

### Comparison
- Show budgeted year vs last year side-by-side
- Show percentage of total for each line item

---

## Excluded Sheets
- **Activity** - Not used
- **Allocation** - Not used

---

## Missing Month Compensation

### Purpose
Allow accurate budgeting when current year is not yet complete

### Logic
When creating a budget before year-end:
1. Calculate elapsed months (including partial month)
   - Example: November 15th = 10.5 months elapsed
2. Get totals for current year-to-date
3. Calculate average per month: `Total ÷ Elapsed Months`
4. Extrapolate to full year: `Average × 12`

### Example
```
Budget creation date: November 15, 2026
Budgeting for: 2027
Elapsed months in 2026: 10.5

Income YTD: 10,500,000
Avg/month: 10,500,000 ÷ 10.5 = 1,000,000
Full year estimate: 1,000,000 × 12 = 12,000,000
```

### Application
Applies to all sections using "last year" data:
- Growth (income history)
- Capacity (income calculations)
- Collection (balances and payments)
- OpEx (expense averages)
- Taxes (tax averages)
- CapEx (current year spending)

### User Interface

#### Layout
- **Tabbed interface** with all sections as tabs:
  1. Growth
  2. Capacity
  3. Collection
  4. Result
  5. Personnel
  6. OpEx
  7. Taxes
  8. CapEx
  9. P&L

#### Save & Continue
- Save progress at any time
- Resume later from where left off
- Auto-save option (TBD)

#### Cross-Tab Dependencies
Tabs affect each other - changes in one tab update related tabs:
- **Growth** → Result (Growth budget value)
- **Capacity** → Result (Capacity budget value), Personnel (new hires)
- **Collection** → Result (Collection budget value)
- **Result** → P&L (Revenue)
- **Personnel** → P&L (Salaries), Capacity (headcount)
- **OpEx** → P&L (Cost of Sales, Sales Commissions, OpEx)
- **Taxes** → P&L (VAT, Taxes)
- **CapEx** → P&L (CapEx)

#### Real-time Updates
- When switching tabs, recalculate affected values
- Show indicators if values have changed due to edits in other tabs

#### Budget Selection
- Dropdown/selector to choose which year's budget to view/edit
- Can open and view previous year budgets
- No side-by-side comparison view needed

#### Budget Editability
- **Current FY budget**: Fully editable
- **Past FY budgets**: Locked (read-only) once the financial year ends
- Example: In 2026, the 2026 budget is editable; 2025 and earlier are locked

#### Financial Year
- Use existing system settings for FY start/end dates
- All calculations respect configured FY period

### Permissions
- **Super Admin**: Full access - create, edit, view budgets
- **Finance**: View only access

---

## Budget Finalization & System Updates

### Purpose
Once budget is finalized, push data to relevant parts of the system

### Finalization Process
- "Finalize Budget" action (Super Admin only)
- Confirmation prompt before applying
- Creates audit log of changes

### System Updates on Finalization

| Source Tab | Target | Field Updated |
|------------|--------|---------------|
| Result (Final values) | Products | Yearly income target |
| Personnel (Proposed Salary) | Employees | Salary for next FY |
| Capacity (New Hires) | TBD | Hiring plan/positions |
| OpEx | Expense Categories | Budget amount/percentage |
| Taxes | Expense Categories (Tax) | Budget amount/percentage |
| CapEx | Expense Categories (CapEx) | Budget amount/percentage |

### Considerations
- Updates should apply for the **budgeted year**, not immediately
- Option to preview what will be updated before confirming
- Ability to re-finalize if budget is edited (with warnings)

---

## Notes
(Additional notes will be added as requirements are gathered)

---

*Document created: January 2025*
*Status: Requirements Complete*
*Implementation Plan: See budget-implementation-plan.md*
