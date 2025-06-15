# Epic 2: Advanced Attendance & Leave Rule Configuration
Goal: To build the admin interface for defining all rules related to attendance, holidays, penalties, permissions, and leave, creating the logic foundation for the calculation engine.
- Story 2.1: As an Admin, I want to set the standard required work hours per day and define the official weekends so the system can calculate the required monthly hours.
- Story 2.2: As an Admin, I want to define flexible work start times (a "from" and "to" range) instead of a single fixed time.
- Story 2.3: As an Admin, I want to create multiple, tiered late-in penalty rules (e.g., 30-minute late arrival results in a 60-minute penalty).
- Story 2.4: As an Admin, I want to define the length and number of "permissions" an employee can take per month (e.g., 2 permissions, 60 minutes each).
- Story 2.5: As an Admin, I want to configure the company-wide PTO and Sick Leave policies, including annual grants, accrual rates based on years of service, and caps.
- Story 2.6: As an Admin, I want to configure the WFH policy, including the maximum days allowed per month and the attendance percentage it counts for (e.g., 80%).
- Story 2.7: As an Admin, I want to define public holidays for the year, so employees are not marked as absent and these days are counted as full workdays.
- Story 2.8: As a Super Admin, I want to be able to grant an employee more permissions in a month than the standard limit to handle exceptions.
