---
mode: agent
name: Changelog Creation
description: |
  This agent is designed to assist in creating and maintaining a project changelog for the QFlow project.
  It will check for an existing CHANGELOG.md file, create a new one if it doesn't exist, or update the existing one with new changes.
  The agent will ensure that all changes are documented clearly and concisely, following proper Markdown formatting.
---

# QFlow Changelog Management

I need you to help maintain our project's CHANGELOG.md file.

## Process

1. First, check if a CHANGELOG.md file exists in the root directory.

2. If the file DOES NOT exist:

   - Create a new CHANGELOG.md with the project description at the top
   - Create a Level 2 Header (##) with today's date and version number 0.1.1
   - Search the codebase for existing functionality, including both:
     - Functionality in Laravel Modules (Modules directory)
     - Functionality in standard Laravel file structure (app, config, resources, routes, etc.)
   - Group the functionality by both module and core Laravel components
   - For each feature, add a one-line description
   - Format the file using proper Markdown

3. If the file ALREADY exists:
   - Review the file and identify the last version number
   - If not given a version to use, Suggest a new version number based on the scale of changes since the last update
     - MAJOR version for incompatible API changes (x.0.0)
     - MINOR version for new functionality in a backward compatible manner (0.x.0)
     - PATCH version for backward compatible bug fixes (0.0.x)
   - Ask me to confirm the version number or provide an alternative
   - After confirmation, create a new section with today's date and the confirmed version
   - Thoroughly scan the entire codebase for all existing functionality
   - Compare this functionality with what's already documented in the CHANGELOG.md
   - Document any missing or undocumented functionality in the new version
   - List all significant additions grouped by:
     - Core Laravel components (Application Core, Web Interface, API & Routes, Database Structure)
     - Laravel Modules (Core, Authentication, User, Organization, etc.)
   - Each entry should be bullet-pointed with a brief description

## Format

The CHANGELOG.md should follow this format:

```markdown
# QFlow Changelog

QFlow is a comprehensive queue management and customer flow platform designed to streamline service operations.

## [Version] - YYYY-MM-DD

### Application Core (Laravel Base)

- Added/Changed/Fixed: Description of core Laravel application changes
- Added/Changed/Fixed: Description of another change

### Web Interface

- Added/Changed/Fixed: Description of UI/UX and frontend changes
- Added/Changed/Fixed: Description of another change

### API & Routes

- Added/Changed/Fixed: Description of API or route-related changes
- Added/Changed/Fixed: Description of another change

### Database Structure

- Added/Changed/Fixed: Description of database-related changes
- Added/Changed/Fixed: Description of another change

### Core Module

- Added/Changed/Fixed: Description of Core module changes
- Added/Changed/Fixed: Description of another change

### Another Module

- Added/Changed/Fixed: Description of the change
```

Search the entire codebase to identify significant changes, new features, bug fixes, and other notable modifications made since the last update.

Let's start by checking if the CHANGELOG.md file exists in the project root.
