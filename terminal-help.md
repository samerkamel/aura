# Terminal Setup Guide - Aura ERP

## Quick Start
To load the terminal environment with helpful aliases and functions:
```bash
source .env.terminal
```

## Available Commands

### Laravel Artisan Shortcuts
- `art` - Shortcut for `php artisan`
- `migrate` - Run database migrations
- `seed` - Run database seeders
- `fresh` - Fresh migration with seed data
- `cache` - Cache config, routes, and views
- `clear-cache` - Clear all cached files
- `tinker` - Start Laravel Tinker

### Development Tools
- `dev` - Run `npm run dev`
- `build` - Run `npm run build`
- `watch` - Run `npm run watch`

### Module Management
- `module make ModuleName` - Create new module
- `module list` - List all modules

### Database
- `dbseed` - Run all seeders
- `dbseed ClassName` - Run specific seeder

### File Navigation
- `ll` - Detailed file listing
- `lsl` - File listing with pagination
- `..` - Go up one directory
- `...` - Go up two directories

### Git Shortcuts
- `gs` - Git status
- `ga` - Git add
- `gc` - Git commit
- `gp` - Git push
- `gl` - Git log (oneline)

### Composer Shortcuts
- `ci` - Composer install
- `cu` - Composer update
- `cr` - Composer require
- `cda` - Composer dump-autoload

## Project Information
- **Project Root:** `/var/www/vhosts/aura.llc/erp.aura.llc`
- **Web Root:** `public/`
- **Modules:** `Modules/` (Laravel Modules architecture)
- **Database:** SQLite at `database/database.sqlite`

## Test Logins
- **Super Admin:** admin@qflow.test / password
- **Manager:** manager@qflow.test / password
- **Employee:** employee@qflow.test / password

## Useful Commands
```bash
# Check application status
art about

# View all routes
art route:list

# Check module status
art module:list

# Run tests
./vendor/bin/phpunit

# Clear everything and start fresh
fresh
cache

# Check logs
tail -f storage/logs/laravel.log
```

## Environment
- **PHP:** 8.3.6
- **Node:** 20.19.5
- **NPM:** 10.8.2
- **Composer:** 2.8.11
- **Laravel:** 11.x
- **Database:** SQLite