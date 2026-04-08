# POS Layaway Module - Installation Guide

This guide will walk you through installing the POS Layaway Module on your Ultimate POS system.

## 📋 Prerequisites

Before installing the module, ensure your system meets the following requirements:

### System Requirements
- **Ultimate POS**: Version 6.0 or higher
- **PHP**: 8.0 or higher
- **Laravel**: 9.x or 10.x
- **MySQL**: 5.7+ or MariaDB 10.3+
- **Web Server**: Apache 2.4+ or Nginx 1.18+

### Required Packages
- `nwidart/laravel-modules` (^9.0) - Should already be installed with Ultimate POS

### Permissions
- Write access to Ultimate POS directory
- Database modification permissions
- Command line access to the server

## 🚀 Quick Installation (Recommended)

The fastest way to install the module is using our automated installer:

### 1. Download the Module

```bash
# Download from GitHub
wget https://github.com/username/pos-layaway/archive/main.zip
unzip main.zip
cd pos-layaway-main

# Or clone the repository
git clone https://github.com/username/pos-layaway.git
cd pos-layaway
```

### 2. Navigate to Ultimate POS Directory

```bash
# Copy the module to your Ultimate POS installation
cd /path/to/your/ultimate-pos
cp -r /path/to/pos-layaway ./
cd pos-layaway
```

### 3. Run Automated Installer

```bash
php install.php
```

The installer will:
- ✅ Check system requirements
- ✅ Create automatic backup
- ✅ Install module files
- ✅ Run database migrations
- ✅ Apply system patches
- ✅ Register the module
- ✅ Verify installation

### 4. Verify Installation

```bash
php scripts/verify-installation.php
```

## 📖 Manual Installation

If you prefer to install manually or the automated installer fails:

### Step 1: Backup Your System

**⚠️ IMPORTANT: Always backup before installation!**

```bash
# Backup database
php artisan backup:run --only-db

# Backup important files
cp app/Transaction.php app/Transaction.php.backup
cp composer.json composer.json.backup
```

### Step 2: Copy Module Files

```bash
# Copy the module to Modules directory
cp -r pos-layaway/src/Modules/Layaway Modules/

# Set proper permissions
chmod -R 755 Modules/Layaway
chown -R www-data:www-data Modules/Layaway  # Adjust user/group as needed
```

### Step 3: Install Database Migrations

```bash
# Copy system integration migrations
cp pos-layaway/database/migrations/*.php database/migrations/

# Run migrations
php artisan migrate --force
php artisan module:migrate Layaway
```

### Step 4: Apply System Patches

#### Update Transaction Model

Add the following method to your `app/Transaction.php` file before the closing brace:

```php
/**
 * Get the associated layaway
 */
public function layaway()
{
    return $this->belongsTo(\Modules\Layaway\Entities\Layaway::class, 'layaway_id');
}
```

#### Install Console Command

```bash
cp pos-layaway/app/patches/FixLayawayNumbers.php app/Console/Commands/
```

### Step 5: Register Module

```bash
# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Regenerate autoload
composer dump-autoload

# List modules to verify
php artisan module:list
```

### Step 6: Verify Installation

```bash
php pos-layaway/scripts/verify-installation.php
```

## 🔧 Post-Installation Configuration

### 1. Access the Module

After installation, you can access the Layaway module through:
- **Menu**: Layaway → New Layaway / All Layaways
- **URL**: `your-domain.com/layaway`

### 2. Set Up Permissions

1. Go to **Settings** → **User Management** → **Roles**
2. Edit user roles to assign layaway permissions:
   - `layaway.create` - Create new layaways
   - `layaway.view` - View layaways
   - `layaway.update` - Edit layaways
   - `layaway.delete` - Delete layaways
   - `layaway.process_payment` - Process payments

### 3. Configure Module Settings

Navigate to **Layaway** → **Settings** to configure:
- Default down payment percentage
- Payment deadline settings
- Notification preferences
- Number format settings

## 🧪 Testing the Installation

### Basic Functionality Test

1. **Create a Test Layaway**:
   - Go to Layaway → New Layaway
   - Select a customer
   - Add a product
   - Set down payment and deadline
   - Save the layaway

2. **Process a Payment**:
   - Open the layaway
   - Click "Make Payment"
   - Enter payment amount
   - Complete the payment

3. **Verify Data**:
   - Check that layaway appears in All Layaways
   - Verify payment history
   - Confirm transaction integration

### Advanced Testing

Run the comprehensive test suite:

```bash
php scripts/verify-installation.php
```

This will test:
- ✅ File installation
- ✅ Database structure
- ✅ Relationships
- ✅ Functionality
- ✅ Integration

## 🚨 Troubleshooting

### Common Issues

#### 1. Module Not Appearing in Menu
```bash
# Clear caches and regenerate
php artisan config:clear
php artisan cache:clear
php artisan route:clear
composer dump-autoload
```

#### 2. Database Migration Errors
```bash
# Check migration status
php artisan migrate:status

# Run specific migrations
php artisan migrate --path=database/migrations/2025_09_18_070615_add_layaway_id_to_transactions_table.php
```

#### 3. Permission Errors
```bash
# Check file permissions
ls -la Modules/Layaway
chmod -R 755 Modules/Layaway
```

#### 4. Layaway Numbers Not Generating
```bash
# Check sequences table
php artisan tinker
>>> Schema::hasTable('sequences')
>>> \DB::table('sequences')->get()
```

### Log Files

Check these log files for detailed error information:
- `storage/logs/laravel.log` - Application logs
- `storage/logs/layaway-install.log` - Installation logs

### Getting Help

1. **Check Documentation**: Review all documentation files
2. **Run Verification**: Use `php scripts/verify-installation.php`
3. **Check Issues**: Visit the GitHub issues page
4. **Community Support**: Ask in Ultimate POS forums

## 🔄 Uninstalling the Module

If you need to uninstall the module:

### 1. Backup Data
```bash
# Export layaway data if needed
php artisan tinker
>>> \Modules\Layaway\Entities\Layaway::all()->toJson()
```

### 2. Remove Module
```bash
# Remove module directory
rm -rf Modules/Layaway

# Remove migrations (optional)
# rm database/migrations/2025_09_18_*layaway*.php

# Remove console command
rm app/Console/Commands/FixLayawayNumbers.php
```

### 3. Revert System Changes
```bash
# Restore Transaction.php backup
cp app/Transaction.php.backup app/Transaction.php

# Clear caches
php artisan config:clear
php artisan cache:clear
```

## 📚 Next Steps

After successful installation:

1. **Read the Configuration Guide**: [configuration.md](configuration.md)
2. **Review API Documentation**: [api.md](api.md)
3. **Train Your Staff**: Familiarize users with layaway workflows
4. **Set Up Backups**: Ensure regular database backups include layaway data

---

**Need help?** Check our [Troubleshooting Guide](troubleshooting.md) or open an issue on GitHub.