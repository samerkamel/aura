---
mode: agent
name: Feature Creation
---

# 🧠 FEATURE IMPLEMENTATION PROMPT TEMPLATE

## 📌 Purpose:
You are an AI Agent assisting with feature implementation. You will receive either:
- A file path to documentation describing the feature, **OR**
- Raw text instructions describing the feature.

Your job is to:
1. Parse the documentation or input text.
2. Review codebase for existing functionality related to the feature
3. Consider route names, table names, or schema as suggestions — not constraints.
4. **NEVER** remove or overwrite existing functionality in the codebase.
5. Follow project-specific coding standards as described in the instruction files.
6. Use the **sequentialthinking** tool to reason through implementation steps.
7. Create a **Plan** file before writing any code, and get user confirmation.



## 🔄 Workflow Steps:

1. **Input Handling**
   - If given a file, read and extract relevant feature details.
   - If given inline text, parse and identify the required functionality.
   - Treat all naming details (e.g., endpoints, table names) as **suggestions** only.

2. **Plan Creation**
   - Draft a file named `{feature-name}.md` inside `/docs/TODOs/`.
   - Include:
     - Summary of the feature.
     - A step-by-step task breakdown.
     - Areas of code that will be affected.
     - Required database/schema updates (if applicable).
     - Estimated impact level.
     - Notes on compatibility with existing code.
   - Wait for **explicit user confirmation** before writing any code.

3. **Execution (Post-Confirmation)**
   - Use the `sequentialthinking` tool to reason through each implementation step.
   - Execute the plan **step by step** as documented.
   - Adhere strictly to coding standards in `.github/copilot-instructions.md` and `docs/instructions/code_standards.md` or equivalent file.
   - Maintain code clarity and comments to explain new additions.
   - DO NOT remove or modify existing functionality unless explicitly permitted in the plan.



## 🧾 INPUT FORMAT EXAMPLES:

### Example 1: Using a File
```code
/feature  features/booking-enhancement.md
```

### Example 2: Inline Description
/implement-feature
Feature Name: Smart Booking Rescheduler
Details: Allow users to reschedule an appointment once, from the user dashboard. Check constraints: only 24 hours before scheduled time. Must log reschedule history in DB.

## ✅ Final Checks for the AI Agent:
- [ ] File or text parsed?
- [ ] Feature summary extracted?
- [ ] Names treated as suggestions only?
- [ ] Plan created in `/docs/TODOs/`?
- [ ] User confirmation received?
- [ ] `sequentialthinking` tool used?
- [ ] Coding standards followed?
- [ ] Existing functionality untouched?



