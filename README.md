# Aura - HR Management System

<p align="center">
  <strong>A comprehensive HR Management System built with Laravel and modular architecture</strong>
</p>

<p align="center">
<img src="https://img.shields.io/badge/Laravel-11.x-red?style=flat-square&logo=laravel" alt="Laravel Version">
<img src="https://img.shields.io/badge/PHP-8.2%2B-blue?style=flat-square&logo=php" alt="PHP Version">
<img src="https://img.shields.io/badge/Vuexy-Admin%20Template-purple?style=flat-square" alt="Vuexy Template">
<img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License">
</p>

## About Aura

Aura is a comprehensive HR Management System built with Laravel's elegant syntax and modular architecture. Our platform streamlines human resource operations through intuitive interfaces and powerful automation features:

## About Aura

Aura is a comprehensive HR Management System built with Laravel's elegant syntax and modular architecture. Our platform streamlines human resource operations through intuitive interfaces and powerful automation features:

## Core Modules

### 🏢 HR Management
- **Employee Management** - Complete employee lifecycle management
- **Document Management** - Secure storage and organization of employee documents
- **Onboarding & Offboarding** - Streamlined processes for new hires and departures

### ⏰ Attendance System
- **Time Tracking** - Comprehensive attendance logging and monitoring
- **Attendance Rules** - Flexible rule engine for different employee types
- **Public Holidays** - Automated holiday management and calculations
- **Work From Home** - Remote work tracking and approval workflows

### 🏖️ Leave Management
- **Leave Policies** - Configurable leave types and entitlements
- **Leave Applications** - Digital leave request and approval process
- **Balance Tracking** - Real-time leave balance calculations
- **Policy Tiers** - Different leave policies based on tenure and role

### 💰 Payroll System
- **Salary Processing** - Automated payroll calculations
- **Billable Hours** - Project-based time tracking and billing
- **Bank Exports** - Direct export to banking systems
- **Payroll Reports** - Comprehensive salary and tax reporting

### 📋 Asset Management
- **Asset Tracking** - Complete lifecycle management of company assets
- **Employee Assignments** - Track asset allocation to employees
- **Maintenance Scheduling** - Automated maintenance reminders
- **Asset Reporting** - Detailed asset utilization reports

### 📄 Letter Generator
- **Document Templates** - Professional letter and certificate templates
- **Automated Generation** - Dynamic document creation with employee data
- **Multi-language Support** - English and Arabic document generation
- **Digital Signatures** - Secure document authentication

## Features

- **🎨 Modern UI** - Built with Vuexy Admin Template for exceptional user experience
- **🔧 Modular Architecture** - Laravel Modules for scalable development
- **🔐 Role-based Access** - Comprehensive permission system using Spatie Laravel Permission
- **📊 Advanced Reporting** - Detailed analytics and reporting capabilities
- **🌐 Multi-language** - Support for multiple languages including Arabic
- **📱 Responsive Design** - Optimized for desktop, tablet, and mobile devices
- **🔄 API Integration** - RESTful APIs for third-party integrations
- **📈 Real-time Updates** - Live data updates and notifications

## Technology Stack

- **Backend**: Laravel 11.x with PHP 8.2+
- **Frontend**: Vuexy Admin Template with Bootstrap 5
- **Database**: MySQL with Eloquent ORM
- **Authentication**: Laravel Sanctum
- **Permissions**: Spatie Laravel Permission
- **File Storage**: Laravel Storage with multiple driver support
- **Task Queue**: Laravel Queue for background processing
- **Testing**: PHPUnit with Feature and Unit tests

## Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL 8.0 or higher

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/samerkamel/aura.git
   cd aura
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Build assets**
   ```bash
   npm run build
   ```

7. **Start the development server**
   ```bash
   php artisan serve
   ```

## Development Guidelines

### Code Quality Standards
- All code must follow Laravel best practices and PSR standards
- PHPDoc documentation required for all classes and methods
- Minimum 85% test coverage for new features
- All UI components must adhere to Vuexy design guidelines

### Testing
```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific module tests
php artisan test tests/Feature/HR/
```

### Documentation
Comprehensive documentation is available in the `/docs` directory:
- [Project Structure](docs/project-structure.md)
- [API Reference](docs/API/api.md)
- [Database Schema](docs/database/database_tables.md)
- [Authentication Implementation](docs/authentication-implementation.md)

## Contributing

We welcome contributions to Aura! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

### Contribution Requirements
- All new features must include comprehensive tests
- Follow the existing code style and documentation standards
- Update relevant documentation
- Ensure all tests pass before submitting

## Security

If you discover any security vulnerabilities, please email the development team immediately. All security vulnerabilities will be promptly addressed.

## License

Aura is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

<p align="center">
  <strong>Built with ❤️ using Laravel and Vuexy</strong>
</p>
