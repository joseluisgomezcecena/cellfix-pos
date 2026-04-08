# Task Completion Workflow

## After Making Code Changes

### 1. Clear Application Caches
Always clear caches after modifying configuration, routes, or adding modules:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 2. Rebuild Caches (Production/Testing)
For performance in production or when testing:

```bash
php artisan config:cache
php artisan route:cache
```

**Note**: Don't cache routes during active development as it prevents new routes from being detected.

## After Database Changes

### 1. Run Migrations
```bash
# All pending migrations
php artisan migrate

# Specific module
php artisan module:migrate ModuleName

# Specific path
php artisan migrate --path=Modules/ModuleName/Database/Migrations
```

### 2. Check Migration Status
```bash
php artisan migrate:status
php artisan module:migrate-status
```

### 3. Clear Query Cache
```bash
php artisan cache:clear
```

## After Adding/Modifying a Module

### Complete Module Installation Checklist

1. **Verify Folder Name**
   - Must be StudlyCase matching `module.json` "name" field
   - Example: `InventoryMultiLocation` NOT `inventory-multi-location-master`

2. **Install Dependencies** (if composer.json exists)
   ```bash
   cd Modules/ModuleName
   composer install --no-dev --optimize-autoloader
   ```

3. **Run Migrations**
   ```bash
   php artisan module:migrate ModuleName
   # OR
   php artisan migrate --path=Modules/ModuleName/Database/Migrations
   ```

4. **Enable Module**
   ```bash
   php artisan module:enable ModuleName
   ```

5. **Clear & Cache**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan config:cache
   php artisan route:cache
   ```

6. **Verify Activation**
   ```bash
   php artisan module:list
   # Should show [Enabled] ModuleName
   ```

## After Adding New Classes/Files

### 1. Dump Autoload
When adding new PHP classes outside of standard directories:

```bash
composer dump-autoload
```

### 2. Clear Application Cache
```bash
php artisan cache:clear
```

## Code Quality Checks

### 1. PHP CS Fixer (Code Style)
Run PHP CS Fixer to format code according to PSR-2:

```bash
# Check what would be fixed
php-cs-fixer fix --dry-run --diff

# Fix code style issues
php-cs-fixer fix
```

### 2. Run Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## Permission-Related Changes

### Clear Permission Cache
After adding new permissions or roles:

```bash
php artisan permission:cache-reset
```

## File Permission Issues

### Fix Storage Permissions
If encountering permission errors with logs or cache:

```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chown -R www:www storage/
chown -R www:www bootstrap/cache/
```

## Development Best Practices

### Before Committing Code
1. ✅ Run code style fixer
2. ✅ Run tests
3. ✅ Clear all caches
4. ✅ Verify functionality in browser
5. ✅ Check for debug statements/console.logs
6. ✅ Review changed files

### Before Deploying to Production
1. ✅ Run migrations on staging first
2. ✅ Run full test suite
3. ✅ Clear all caches
4. ✅ Rebuild production caches
5. ✅ Backup database
6. ✅ Test in production-like environment
7. ✅ Check error logs

## Quick Reference

### Most Common Post-Development Commands
```bash
# After any code change
php artisan config:clear && php artisan cache:clear && php artisan route:clear

# After adding a module
php artisan module:migrate ModuleName && php artisan module:enable ModuleName && php artisan config:cache && php artisan route:cache

# After permission changes
php artisan permission:cache-reset && php artisan cache:clear

# Full cache rebuild (use sparingly)
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear && php artisan config:cache && php artisan route:cache
```

## Troubleshooting Common Issues

### "Module not found" Error
```bash
# 1. Check folder name matches module.json
# 2. Clear caches
php artisan config:clear && php artisan cache:clear
# 3. Dump autoload
composer dump-autoload
```

### "Route not found" Error
```bash
# 1. Clear route cache
php artisan route:clear
# 2. Check route:list
php artisan route:list | grep modulename
# 3. Recache if needed
php artisan route:cache
```

### "View not found" Error
```bash
# 1. Clear view cache
php artisan view:clear
# 2. Check namespace registration in ServiceProvider
# 3. Verify view file exists
```

### "Permission denied" Database Error
```bash
# Check .env database credentials
# Verify database connection
php artisan tinker
>>> DB::connection()->getPdo();
```