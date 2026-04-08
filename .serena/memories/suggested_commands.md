# Suggested Development Commands

## Laravel Artisan Commands

### Module Management
```bash
# List all modules and their status
php artisan module:list

# Enable a module
php artisan module:enable ModuleName

# Disable a module
php artisan module:disable ModuleName

# Create a new module
php artisan module:make ModuleName

# Delete a module
php artisan module:delete ModuleName
```

### Module Development
```bash
# Create module components
php artisan module:make-controller ControllerName ModuleName
php artisan module:make-model ModelName ModuleName
php artisan module:make-migration create_table_name ModuleName
php artisan module:make-request RequestName ModuleName
php artisan module:make-provider ProviderName ModuleName
php artisan module:make-seed SeederName ModuleName

# Run module migrations
php artisan module:migrate ModuleName
php artisan module:migrate-rollback ModuleName
php artisan module:migrate-refresh ModuleName
php artisan module:migrate-status

# Run module seeders
php artisan module:seed ModuleName

# Publish module assets
php artisan module:publish ModuleName
```

### Database Operations
```bash
# Run all migrations
php artisan migrate

# Run specific migration path
php artisan migrate --path=Modules/ModuleName/Database/Migrations

# Rollback migrations
php artisan migrate:rollback

# Refresh database (drop and re-migrate)
php artisan migrate:fresh

# Create new migration
php artisan make:migration create_table_name

# Run seeders
php artisan db:seed
```

### Cache Management
```bash
# Clear all caches (run after module changes)
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache configuration (for production)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear and recache everything
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
php artisan config:cache && php artisan route:cache
```

### Code Generation
```bash
# Generate controller
php artisan make:controller ControllerName

# Generate model
php artisan make:model ModelName

# Generate migration
php artisan make:migration migration_name

# Generate request validation
php artisan make:request RequestName

# Generate middleware
php artisan make:middleware MiddlewareName
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Run tests with coverage
php artisan test --coverage

# Run PHPUnit directly
./vendor/bin/phpunit
```

### Permissions & Roles
```bash
# Clear permission cache
php artisan permission:cache-reset
```

### Development Server
```bash
# Start development server
php artisan serve

# Start on specific host/port
php artisan serve --host=0.0.0.0 --port=8000
```

## Composer Commands

```bash
# Install dependencies
composer install

# Update dependencies
composer update

# Dump autoload (after adding new classes)
composer dump-autoload

# Install without dev dependencies (production)
composer install --no-dev --optimize-autoloader
```

## System Commands

### File Operations
```bash
# Find files
find /www/wwwroot/dev.celfix.mx -name "*.php"

# Search in files
grep -r "search_term" /www/wwwroot/dev.celfix.mx/app/

# List directory contents
ls -la /www/wwwroot/dev.celfix.mx/Modules/

# Change to project directory
cd /www/wwwroot/dev.celfix.mx
```

### File Permissions
```bash
# Set storage permissions
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/

# Set owner (if needed)
chown -R www:www /www/wwwroot/dev.celfix.mx
```

### Git Commands (when initialized)
```bash
# Check status
git status

# Add changes
git add .

# Commit changes
git commit -m "commit message"

# Pull changes
git pull origin main

# Push changes
git push origin main
```

## Quick Development Workflow

### After Modifying Code
```bash
# 1. Clear caches
php artisan config:clear && php artisan cache:clear && php artisan route:clear

# 2. Recache (optional, for performance)
php artisan config:cache && php artisan route:cache
```

### After Adding a New Module
```bash
# 1. Ensure correct folder name (StudlyCase)
# 2. Run migrations
php artisan module:migrate ModuleName

# 3. Enable module
php artisan module:enable ModuleName

# 4. Clear and cache
php artisan config:cache && php artisan route:cache

# 5. Verify
php artisan module:list
```

### After Database Schema Changes
```bash
# 1. Create migration
php artisan make:migration migration_name

# 2. Run migration
php artisan migrate

# 3. Check status
php artisan migrate:status
```

## Troubleshooting Commands

```bash
# Check Laravel version
php artisan --version

# Check PHP version
php -v

# List all routes
php artisan route:list

# Check module status
php artisan module:list

# View logs
tail -f storage/logs/laravel.log

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();
```