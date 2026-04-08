# Troubleshooting Guide

This guide helps you resolve common issues with the POS Layaway Module.

## 🔍 General Diagnostics

### Quick Health Check

Run this command to verify module status:

```bash
php scripts/verify-installation.php
```

### Check Log Files

Monitor these log files for errors:

```bash
# Application logs
tail -f storage/logs/laravel.log

# Installation logs
tail -f storage/logs/layaway-install.log

# Web server logs
tail -f /var/log/apache2/error.log  # Apache
tail -f /var/log/nginx/error.log    # Nginx
```

## 🚨 Common Issues and Solutions

### 1. Module Not Appearing in Menu

**Symptoms:**
- Layaway menu items not visible
- Module installed but not accessible

**Solutions:**

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Regenerate autoload
composer dump-autoload

# Check module status
php artisan module:list
```

**If still not working:**

```bash
# Force module registration
php artisan module:enable Layaway
php artisan module:publish Layaway

# Check for errors
php artisan module:list | grep -i layaway
```

### 2. Database Migration Errors

**Symptoms:**
- Installation fails during migration
- Tables not created
- Foreign key constraint errors

**Check Migration Status:**

```bash
php artisan migrate:status
```

**Run Specific Migrations:**

```bash
# System migrations
php artisan migrate --path=database/migrations/2025_09_18_070615_add_layaway_id_to_transactions_table.php
php artisan migrate --path=database/migrations/2025_09_18_070942_add_transaction_payment_id_to_layaway_payments_table.php
php artisan migrate --path=database/migrations/2025_09_18_083239_create_sequences_table.php

# Module migrations
php artisan module:migrate Layaway --force
```

**Fix Foreign Key Issues:**

```sql
-- Check if referenced tables exist
SHOW TABLES LIKE 'transactions';
SHOW TABLES LIKE 'transaction_payments';

-- Check table structure
DESCRIBE transactions;
DESCRIBE transaction_payments;
```

### 3. Layaway Number Generation Fails

**Symptoms:**
- Error: "Failed to generate unique layaway number"
- Duplicate number errors
- Numbers not following expected format

**Check Sequences Table:**

```bash
php artisan tinker
>>> \DB::table('sequences')->get()
>>> Schema::hasTable('sequences')
```

**Reset Sequence Counter:**

```sql
-- Reset sequence for today
DELETE FROM sequences WHERE `key` LIKE 'layaway_1_%';

-- Or manually set sequence
INSERT INTO sequences (`key`, `value`, created_at, updated_at)
VALUES ('layaway_1_20250917', 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE `value` = 0;
```

**Test Number Generation:**

```bash
php artisan tinker
>>> \Modules\Layaway\Entities\Layaway::generateLayawayNumber(1)
```

### 4. Payment Processing Errors

**Symptoms:**
- "Too few arguments" error during payment
- 500 Internal Server Error on payment submission
- Payments not recording in transaction system

**Check TransactionUtil Integration:**

```bash
php artisan tinker
>>> $util = new \App\Utils\TransactionUtil()
>>> $util->setAndGetReferenceCount('payment', 1)
>>> $util->generateReferenceNumber('payment', 1, 1)
```

**Verify Transaction Relationship:**

```bash
php artisan tinker
>>> $transaction = new \App\Transaction()
>>> method_exists($transaction, 'layaway')
```

**Check Payment Controller:**

Verify that `LayawayPaymentController.php` has the correct `generateReferenceNumber` calls:

```php
// Should be (correct):
$ref_count = $this->transactionUtil->setAndGetReferenceCount('payment', $business_id);
$payment_ref_no = $this->transactionUtil->generateReferenceNumber('payment', $ref_count, $business_id);

// Not (incorrect):
$payment_ref_no = $this->transactionUtil->generateReferenceNumber();
```

### 5. Permission Denied Errors

**Symptoms:**
- 403 Unauthorized errors
- "You don't have permission" messages
- Module accessible to admin but not other users

**Check User Permissions:**

```bash
php artisan tinker
>>> $user = \App\User::find(USER_ID)
>>> $user->can('layaway.view')
>>> $user->can('layaway.create')
```

**Assign Permissions:**

1. Go to **Settings** → **User Management** → **Roles**
2. Select the user's role
3. Check layaway permissions:
   - `layaway.create`
   - `layaway.view`
   - `layaway.update`
   - `layaway.delete`
   - `layaway.process_payment`

**Refresh Permissions:**

```bash
php artisan permission:cache-reset  # If using spatie/permission
php artisan cache:clear
```

### 6. File Permission Issues

**Symptoms:**
- "Permission denied" errors
- Files not accessible via web
- Module views not loading

**Fix File Permissions:**

```bash
# Set correct ownership
chown -R www-data:www-data Modules/Layaway
chown -R www-data:www-data storage/

# Set correct permissions
chmod -R 755 Modules/Layaway
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

**Check Directory Permissions:**

```bash
ls -la Modules/
ls -la Modules/Layaway/
ls -la storage/logs/
```

### 7. JavaScript/CSS Not Loading

**Symptoms:**
- Module pages look unstyled
- JavaScript functionality not working
- Console errors in browser

**Check Asset Compilation:**

```bash
# If using Laravel Mix
npm run dev
npm run production

# Clear view cache
php artisan view:clear
```

**Verify Asset Paths:**

Check that asset paths in views are correct:

```php
// Should use module assets
asset('modules/layaway/css/app.css')
asset('modules/layaway/js/app.js')
```

### 8. Database Connection Issues

**Symptoms:**
- "Connection refused" errors
- Module installation fails at database step
- Tables not accessible

**Check Database Configuration:**

```bash
# Test database connection
php artisan tinker
>>> \DB::connection()->getPdo()
```

**Verify .env Settings:**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 9. Class Not Found Errors

**Symptoms:**
- "Class 'Modules\Layaway\...' not found"
- Autoload errors
- Module components not loading

**Regenerate Autoload:**

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

**Check Module Registration:**

```bash
php artisan module:list
```

**Verify Composer Autoload:**

Check `composer.json` includes module namespace:

```json
{
    "autoload": {
        "psr-4": {
            "Modules\\Layaway\\": "Modules/Layaway/"
        }
    }
}
```

### 10. Menu Items Showing but Routes Not Working

**Symptoms:**
- Menu items visible but clicking gives 404
- Routes not registered
- "Route not found" errors

**Check Route Registration:**

```bash
php artisan route:list | grep layaway
```

**Clear Route Cache:**

```bash
php artisan route:clear
php artisan route:cache
```

**Verify Route Files:**

Check that route files exist:
- `Modules/Layaway/Routes/web.php`
- `Modules/Layaway/Routes/api.php`

## 🔧 Advanced Debugging

### Enable Debug Mode

Add to `.env` for detailed error messages:

```env
APP_DEBUG=true
APP_LOG_LEVEL=debug
```

### Database Query Logging

Add to `AppServiceProvider.php`:

```php
public function boot()
{
    if (config('app.debug')) {
        \DB::listen(function ($query) {
            \Log::info('Query: ' . $query->sql);
            \Log::info('Bindings: ' . json_encode($query->bindings));
        });
    }
}
```

### Module-Specific Debugging

Add debug logging to module files:

```php
// In controllers or models
\Log::info('Layaway debug:', [
    'action' => 'payment_processing',
    'data' => $request->all()
]);
```

## 🗄️ Database Recovery

### Restore from Backup

If installation corrupted your database:

```bash
# Restore from backup
mysql -u username -p database_name < backup_file.sql

# Or using Laravel backup
php artisan backup:restore
```

### Manual Table Recreation

If specific tables are corrupted:

```sql
-- Drop and recreate layaway tables
DROP TABLE IF EXISTS layaway_payments;
DROP TABLE IF EXISTS layaway_items;
DROP TABLE IF EXISTS layaways;
DROP TABLE IF EXISTS sequences;

-- Re-run migrations
```

Then run:

```bash
php artisan module:migrate Layaway --force
```

## 🔄 Complete Module Reset

If all else fails, completely reset the module:

### 1. Remove Module Files

```bash
rm -rf Modules/Layaway
rm -f app/Console/Commands/FixLayawayNumbers.php
```

### 2. Rollback Database Changes

```bash
# Rollback migrations
php artisan migrate:rollback --step=4

# Remove migration files
rm database/migrations/*layaway*
rm database/migrations/*sequences*
```

### 3. Revert System Changes

```bash
# Restore Transaction.php from backup
cp app/Transaction.php.backup app/Transaction.php
```

### 4. Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
composer dump-autoload
```

### 5. Reinstall

```bash
# Download fresh copy and reinstall
php install.php
```

## 📞 Getting Help

### Before Asking for Help

1. **Run Verification Script:**
   ```bash
   php scripts/verify-installation.php
   ```

2. **Check Log Files:**
   ```bash
   tail -50 storage/logs/laravel.log
   ```

3. **Gather System Information:**
   ```bash
   php --version
   php artisan --version
   cat .env | grep -E "DB_|APP_"
   ```

### Where to Get Help

1. **GitHub Issues**: https://github.com/username/pos-layaway/issues
2. **Ultimate POS Community**: Official forums and Discord
3. **Documentation**: Review all documentation files
4. **Stack Overflow**: Tag questions with `ultimate-pos` and `layaway`

### Information to Include in Support Requests

- PHP version
- Ultimate POS version
- Complete error messages
- Steps to reproduce the issue
- Log file excerpts
- Output of verification script

---

**Remember**: Always backup your database and files before making any changes or attempting fixes!