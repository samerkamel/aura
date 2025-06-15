As a monolith, the system's internal components will communicate through service classes and method calls, not internal HTTP APIs. The primary external integrations are file-based.

- Attendance Log Import:
  - Source: CSV file from fingerprint attendance machine.
  - Format: A specific CSV structure must be handled by the system's import logic.
  - Process: An admin-triggered action that parses the CSV and stores the raw log data.
- Bank Payroll Export:
  - Target: Excel (.xlsx) file.
  - Format: A specific multi-column format as required by the designated bank.
  - Process: An admin-triggered action after a payroll run is finalized.
