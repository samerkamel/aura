# Aura ERP Integration Analysis Report

**Date**: December 31, 2025
**Version**: 1.0
**Status**: Analysis Complete - Awaiting Prioritization

---

## Executive Summary

The Aura ERP system consists of 12 modules with 62 models. While individual modules are well-built, there are significant **integration gaps** that prevent the system from functioning as a unified financial ecosystem. The main issues are:

1. **Payroll costs don't flow to Accounting** - Labor expenses are invisible to the financial ledger
2. **Project costs are isolated** - Not reflected in organizational expenses
3. **Two separate "Estimate" systems** - Sales estimates (Accounting) vs. Time estimates (Project)
4. **Revenue tracking is fragmented** - Same payment tracked in 3 places
5. **No event-driven synchronization** - Modules don't communicate changes

---

## Module Inventory

| Module | Models | Purpose | Integration Status |
|--------|--------|---------|-------------------|
| **HR** | 6 | Employee & documents | ✅ Core - Well integrated |
| **Attendance** | 5 | Time tracking | ✅ Core - Well integrated |
| **Leave** | 2 | Leave management | ✅ Core - Well integrated |
| **Payroll** | 10 | Salary processing | ⚠️ Isolated from Accounting |
| **Project** | 15 | Project delivery & finance | ⚠️ Partial integration |
| **Accounting** | 17 | Financial management | ⚠️ Missing income from other modules |
| **Invoicing** | 7 | Invoice generation | ✅ Well integrated |
| **AssetManager** | 2 | Asset lifecycle | ✅ Standalone - OK |
| **LetterGenerator** | 1 | Document templates | ✅ Standalone - OK |
| **Settings** | 1 | Configuration | ✅ Standalone - OK |
| **SelfService** | 1 | Employee portal | ✅ Standalone - OK |

---

## Critical Integration Gaps

### Gap 1: Payroll → Accounting (CRITICAL)

**Current State:**
```
PayrollRun created → Salary paid → NO RECORD in Accounting
```

**Impact:**
- Profit/Loss statements are incomplete
- Cash flow projections missing labor costs (typically 60-70% of expenses)
- No audit trail of payroll as expense
- Cannot reconcile bank statements with accounting ledger

**Missing Link:**
- `PayrollRun` should create `ExpenseSchedule` entries automatically
- Should be categorized under "Payroll Expense" category
- Should link to projects if employee worked on specific projects

**Files Affected:**
- `Modules/Payroll/app/Models/PayrollRun.php` - No relationship to Accounting
- `Modules/Accounting/app/Models/ExpenseSchedule.php` - No payroll_run_id

---

### Gap 2: Project Costs → Accounting (HIGH)

**Current State:**
```
ProjectCost recorded → Stays in Project module → Never synced to Accounting
```

**Impact:**
- Organizational expense reports don't include project costs
- Budget planning disconnected from actual project spending
- CFO view is incomplete

**Missing Link:**
- `ProjectCost` has an unused `expense_id` field
- Should sync to `ExpenseSchedule` with project reference
- Should respect expense categories

**Files Affected:**
- `Modules/Project/app/Models/ProjectCost.php` - Line 25: `expense_id` exists but unused
- `Modules/Accounting/app/Models/ExpenseSchedule.php` - No project_cost_id

---

### Gap 3: Two Estimate Systems (MEDIUM)

**System 1: Sales Estimates (Accounting)**
```
Location: Modules/Accounting/app/Models/Estimate.php
Purpose: Client quotations with line items
Flow: Draft → Sent → Approved → Contract
```

**System 2: Time Estimates (Project)**
```
Location: Modules/Project/app/Models/ProjectTimeEstimate.php
Purpose: Internal hour tracking per task
Flow: Standalone - tracks estimated vs actual hours
```

**Current Relationship:** NONE - These are parallel systems

**Missing Link:**
- Sales Estimate line items could reference Time Estimates
- Time Estimate hours × hourly rate = line item amount
- Variance in time estimate should flag financial impact

---

### Gap 4: Contract → Project Revenue (MEDIUM)

**Current State:**
```
Estimate → Contract (conversion works)
Contract → ContractPayment schedule (works)
BUT: Project → ProjectRevenue (manual entry required)
```

**Impact:**
- Project profitability dashboard requires manual data entry
- Revenue recognition is manual and error-prone
- Cannot auto-calculate project margin

**Missing Link:**
- When Contract is linked to Project, auto-create ProjectRevenue entries
- Sync ContractPayment.status with ProjectRevenue.status

---

### Gap 5: Labor Cost Calculation Persistence (HIGH)

**Current State:**
```
JiraWorklog (hours) + Employee.base_salary → Calculated on-demand
Result: Displayed in Project Finance Dashboard
NOT persisted anywhere
```

**Impact:**
- Historical labor costs cannot be audited
- Recalculating costs gives different results if salaries change
- Month-end close doesn't capture labor costs

**Current Calculation Location:**
- `Modules/Project/app/Services/ProjectFinancialService.php`
- Method: `calculateLaborCostsFromWorklogs()`
- Uses: `labor_cost_multiplier` from Settings (currently 2.75)
- Adds: 20% PM overhead

**Missing Link:**
- Should persist labor costs to `ProjectCost` table
- Should be run monthly as part of period close
- Should sync to Accounting as expense

---

## Duplicate Functionality

### Budget Systems (3 separate implementations)

| System | Location | Purpose |
|--------|----------|---------|
| `ProjectBudget` | Project module | Per-project budget by category |
| `ExpenseCategoryBudget` | Accounting module | Organization budget by expense type |
| `ContractPayment` | Accounting module | Income schedule/budget |

**Problem:** No unified budget view comparing income vs. expenses across organization

---

### Payment Tracking (3 separate implementations)

| System | Location | Tracks |
|--------|----------|--------|
| `ContractPayment` | Accounting | Expected income milestones |
| `InvoicePayment` | Invoicing | Actual payments received |
| `ProjectRevenue` | Project | Project-level revenue recognition |

**Problem:** Same payment event recorded in 3 places; reconciliation is manual

---

### Expense Recording (2 separate implementations)

| System | Location | Purpose |
|--------|----------|---------|
| `ProjectCost` | Project | Project-specific costs |
| `ExpenseSchedule` | Accounting | Organizational expenses |

**Problem:** Project costs don't appear in organizational expense reports

---

## Recommended Integration Architecture

### Phase 1: Event-Driven Sync (Foundation)

Create Laravel Events for cross-module communication:

```php
// Events to create:
PayrollRunCompleted::class     → Creates Accounting Expense
ProjectCostCreated::class      → Creates Accounting Expense
ContractCreated::class         → Creates Project Revenue entries
InvoicePaymentReceived::class  → Updates Project Revenue status
EstimateConverted::class       → Links to Project Time Estimates
```

### Phase 2: Unified Financial Service

Create `OrganizationFinancialService`:
- Aggregates all revenue sources (Contracts, Invoices, Project Revenue)
- Aggregates all expenses (Payroll, Project Costs, Accounting Expenses)
- Generates consolidated P&L
- Provides single source of truth for financial dashboards

### Phase 3: Estimate Integration

Link Sales Estimates to Time Estimates:
- Add `time_estimate_id` to `EstimateItem`
- Auto-calculate line item amounts from hours × rate
- Show variance when time estimate actuals differ

### Phase 4: Period Close Process

Create monthly close workflow:
1. Lock previous period data
2. Calculate and persist labor costs
3. Sync all project costs to accounting
4. Reconcile payments across systems
5. Generate period reports

---

## Integration Priority Matrix

| Gap | Business Impact | Technical Effort | Priority |
|-----|-----------------|------------------|----------|
| Payroll → Accounting | Critical (P&L incomplete) | Medium | **P1** |
| Labor Cost Persistence | High (no audit trail) | Medium | **P1** |
| Project Costs → Accounting | High (expense reports incomplete) | Low | **P2** |
| Contract → Project Revenue | Medium (manual entry) | Low | **P2** |
| Estimate Systems Link | Medium (planning disconnect) | High | **P3** |
| Unified Budget View | Medium (reporting gap) | High | **P3** |
| Payment Reconciliation | Medium (data duplication) | High | **P4** |

---

## Questions for Stakeholders

Before proceeding with implementation, please clarify:

1. **Payroll Expense Timing**: Should payroll expenses be recorded when:
   - Payroll is run (accrual basis)?
   - Salaries are paid (cash basis)?
   - Both (with different status)?

2. **Labor Cost Attribution**: How should labor costs be split:
   - 100% to the project worked on?
   - Split across multiple projects by hours?
   - Overhead pool for non-project time?

3. **Historical Data**: Should we:
   - Migrate historical payroll runs to accounting expenses?
   - Start fresh from a specific date?

4. **Estimate Integration**: Do you want:
   - Sales estimates to pull from time estimates automatically?
   - Time estimates to be optional add-on to line items?
   - Keep them separate but add linking capability?

5. **Period Close**: Do you need:
   - Manual period close process?
   - Automatic monthly close?
   - Ability to reopen closed periods?

---

## File Reference

### Key Files for Integration Work

**Payroll → Accounting:**
- `Modules/Payroll/app/Models/PayrollRun.php`
- `Modules/Accounting/app/Models/ExpenseSchedule.php`
- `Modules/Accounting/app/Models/ExpenseCategory.php`
- NEW: `app/Services/PayrollAccountingService.php`

**Project → Accounting:**
- `Modules/Project/app/Models/ProjectCost.php`
- `Modules/Project/app/Services/ProjectFinancialService.php`
- `Modules/Accounting/app/Models/ExpenseSchedule.php`
- NEW: `app/Services/ProjectAccountingService.php`

**Contract → Project:**
- `Modules/Accounting/app/Models/Contract.php`
- `Modules/Project/app/Models/ProjectRevenue.php`
- NEW: `app/Listeners/ContractCreatedListener.php`

**Estimate Integration:**
- `Modules/Accounting/app/Models/Estimate.php`
- `Modules/Accounting/app/Models/EstimateItem.php`
- `Modules/Project/app/Models/ProjectTimeEstimate.php`
- MODIFY: Add `time_estimate_id` to `estimate_items` table

---

## Next Steps

1. Review this analysis and confirm findings
2. Answer clarifying questions above
3. Prioritize integration gaps based on business needs
4. Create detailed implementation plan for P1 items
5. Estimate development effort
6. Begin implementation in order of priority

---

*This document will be updated as decisions are made and implementation progresses.*
