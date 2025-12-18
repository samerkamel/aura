# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Aura is a comprehensive HR Management System built with Laravel 11.x and modular architecture using Laravel Modules. The system includes modules for HR Management, Attendance, Leave, Payroll, Asset Management, and Letter Generation.

## Essential Commands

### Development Server
```bash
php artisan serve
```

### Database Commands
```bash
php artisan migrate              # Run migrations
php artisan migrate:rollback     # Rollback migrations
php artisan db:seed              # Seed the database
php artisan migrate:fresh --seed # Fresh migration with seeding
```

### Testing
```bash
php artisan test                           # Run all tests
php artisan test --coverage                # Run tests with coverage
php artisan test tests/Feature/HR/         # Run specific module tests
php artisan test --filter=TestClassName    # Run specific test class
```

### Code Quality
```bash
php artisan pint          # Format code using Laravel Pint
```

### Module Management
```bash
php artisan module:list                    # List all modules
php artisan module:enable ModuleName       # Enable a module
php artisan module:disable ModuleName      # Disable a module
php artisan module:make ModuleName         # Create a new module
php artisan module:migrate ModuleName      # Run module migrations
php artisan module:seed ModuleName         # Run module seeders
```

### Asset Building
```bash
npm run dev    # Development build with hot reload
npm run build  # Production build
```

### Cache Management
```bash
php artisan cache:clear      # Clear application cache
php artisan config:clear     # Clear config cache
php artisan route:clear      # Clear route cache
php artisan view:clear       # Clear view cache
php artisan optimize:clear   # Clear all caches
```

## Architecture Overview

### Modular Structure
The application uses nwidart/laravel-modules for modular architecture. Each module is self-contained with its own:
- Controllers, Models, and Migrations
- Routes (web.php and api.php)
- Views and Assets
- Tests

Key modules:
- **HR**: Employee management, documents, onboarding/offboarding
- **Attendance**: Time tracking, attendance rules, holidays, WFH management
- **Leave**: Leave policies, applications, balance tracking
- **Payroll**: Salary processing, billable hours, bank exports
- **AssetManager**: Asset lifecycle, assignments, maintenance
- **LetterGenerator**: Document templates, multi-language support

### Frontend Architecture
- Uses Vuexy Admin Template with Bootstrap 5
- Vite for asset bundling
- jQuery and vanilla JavaScript for interactivity
- DataTables for data grids
- Form validation with @form-validation/bundle

### Database Design
- Uses MySQL with Eloquent ORM
- Follows Laravel conventions for migrations and models
- Relationships defined using Eloquent relationships
- Soft deletes implemented where applicable

### Authentication & Authorization
- Laravel Sanctum for API authentication
- Role-based permissions (likely using Spatie Laravel Permission)
- Middleware for route protection

### File Organization
```
Modules/
├── ModuleName/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   ├── Middleware/
│   │   │   └── Requests/
│   │   └── Models/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── resources/
│   │   └── views/
│   ├── routes/
│   │   ├── api.php
│   │   └── web.php
│   └── tests/
│       ├── Feature/
│       └── Unit/
```

## Development Guidelines

### Module Development
- Each module should be self-contained and follow the existing structure
- Use module-specific routes, controllers, and models
- Place module assets in the module's resources directory
- Write tests for new features in the module's tests directory

### Database Conventions
- Use Laravel migrations for all database changes
- Follow Laravel naming conventions for tables and columns
- Use foreign key constraints for relationships
- Implement soft deletes where data retention is important

### Frontend Development
- Follow Vuexy's component structure and styling
- Use Bootstrap 5 utilities and components
- Ensure responsive design for all views
- Use DataTables for data listings with server-side processing

### API Development
- Use RESTful conventions for API endpoints
- Implement proper validation using Form Requests
- Return consistent JSON responses
- Use API resources for response transformation

### Testing Strategy
- Write feature tests for HTTP endpoints
- Write unit tests for business logic
- Use database transactions in tests
- Mock external services in tests