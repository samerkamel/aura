Epic 3: Attendance Processing & Payroll Calculation

Goal: To implement the logic for processing attendance data against the configured rules and calculating the final salary components, culminating in a bank-ready export file.
Story 3.1: As an Admin, I want to import an attendance log for all employees from a standard CSV file for a specific date range.
Story 3.2: As an Admin, I want the system to automatically calculate each employee's total attended hours for the month, correctly applying all late penalties, permissions, and public holidays.
Story 3.3: As an Admin, I want to be able to log PTO, Sick Leave, and WFH days for employees, so they are correctly factored into their final attendance calculation.
Story 3.4: As an Admin, I want to input the number of billable hours for each developer monthly (manually or via CSV import) to be used as a payroll component.
Story 3.5: As an Admin, I want to set the weight of the "Attendance Percentage" and "Billable Hours Percentage" to be used in the final payroll formula.
Story 3.6: As an Admin, I want to view a summary for each employee showing the breakdown of their final percentage calculation (attendance, billable hours, WFH, PTO, penalties) before finalizing payroll.
Story 3.7: As an Admin, after finalizing the monthly payroll, I want to export an Excel sheet containing employee names, bank account numbers, and final salary amounts, formatted exactly as required by our bank.
