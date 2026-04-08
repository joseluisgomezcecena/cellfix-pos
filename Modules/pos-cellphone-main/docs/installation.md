# Installation Guide

This guide provides detailed installation instructions for the POS Cellphone Module.

## 📋 Prerequisites

Before installing, ensure your system meets these requirements:

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

### Step 1: Download the Module

```bash
# Download from GitHub
wget https://github.com/celfix/pos-cellphone/archive/main.zip
unzip main.zip
cd pos-cellphone-main

# Or clone the repository
git clone https://github.com/celfix/pos-cellphone.git
cd pos-cellphone
```

### Step 2: Copy to Ultimate POS

```bash
# Copy module directory to your Ultimate POS installation
cp -r pos-cellphone /path/to/your/ultimate-pos/
cd /path/to/your/ultimate-pos
```

### Step 3: Run Automated Installer

```bash
php pos-cellphone/install.php
```

The installer will:
- ✅ Check system requirements
- ✅ Create automatic backup
- ✅ Install module files
- ✅ Run module seeder
- ✅ Register the module
- ✅ Verify installation

### Step 4: Verify Installation

```bash
php pos-cellphone/scripts/verify-installation.php
```

## 📖 Manual Installation

If you prefer manual installation or the automated installer fails:

### Step 1: Backup Your System

**⚠️ IMPORTANT: Always backup before installation!**

```bash
# Backup database
php artisan backup:run --only-db

# Backup Modules directory (if Cellphone already exists)
cp -r Modules/Cellphone Modules/Cellphone.backup
```

### Step 2: Copy Module Files

```bash
# Copy the module to Modules directory
cp -r pos-cellphone/src/Modules/Cellphone Modules/

# Set proper permissions
chmod -R 755 Modules/Cellphone
chown -R www-data:www-data Modules/Cellphone  # Adjust user/group as needed
```

### Step 3: Run Module Seeder

```bash
# This registers the module version in the system table
php artisan module:seed Cellphone
```

### Step 4: Register Module

```bash
# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Regenerate autoload
composer dump-autoload

# Verify module is listed
php artisan module:list
```

### Step 5: Verify Installation

```bash
# Run verification script
php pos-cellphone/scripts/verify-installation.php

# Or manually check
php artisan tinker
>>> App\System::getProperty('cellphone_version')
>>> exit
```

## 🔧 Post-Installation Configuration

### 1. Access the Module

After installation, refresh your browser and you'll see **"Equipos Celulares"** in the sidebar menu with:
- **Nuevo Celular** - Add new cellphone
- **Todos los Celulares** - View all cellphones

### 2. Set Up Permissions

1. Go to **Settings** → **User Management** → **Roles**
2. Edit user roles to assign cellphone permissions:
   - `cellphone.create` - Create new cellphones
   - `cellphone.view` - View cellphones
   - `cellphone.update` - Edit cellphones
   - `cellphone.delete` - Delete cellphones
   - `cellphone.export` - Export reports

### 3. Configure Module Settings

Navigate to `/Modules/Cellphone/Config/config.php` to configure:
- Default field mappings
- Warranty options
- IMEI validation pattern
- Condition options

## 🧪 Testing the Installation

### Basic Functionality Test

1. **Access the Module**:
   - Log into Ultimate POS
   - Click "Equipos Celulares" in the menu
   - Verify the page loads correctly

2. **Create a Test Cellphone**:
   - Click "Nuevo Celular"
   - Enter test data:
     - IMEI: 123456789012345
     - Marca: Samsung
     - Modelo: Test Model
     - Estado: Nuevo
   - Save and verify it appears in the list

3. **Test Search & Filters**:
   - Use filter options to search by brand
   - Verify results are correct

## 🚨 Troubleshooting

### Module Not Appearing in Menu

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Regenerate autoload
composer dump-autoload

# Check module status
php artisan module:list | grep -i cellphone
```

### Module Version Not Registered

```bash
# Check system table
php artisan tinker
>>> App\System::getProperty('cellphone_version')

# If not found, run seeder again
php artisan module:seed Cellphone
```

### Permission Errors

```bash
# Check file permissions
ls -la Modules/Cellphone

# Fix permissions
chmod -R 755 Modules/Cellphone
chown -R www-data:www-data Modules/Cellphone
```

### Routes Not Working

```bash
# Clear route cache
php artisan route:clear

# Verify routes exist
php artisan route:list | grep cellphone
```

## 📝 Log Files

Check these log files for detailed error information:
- `storage/logs/laravel.log` - Application logs
- `storage/logs/cellphone-install.log` - Installation logs

## 🆘 Getting Help

1. **Check Documentation**: Review all documentation files
2. **Run Verification**: Use `php scripts/verify-installation.php`
3. **Check Issues**: Visit the GitHub issues page
4. **Community Support**: Ask in Ultimate POS forums

## 🔄 Uninstalling the Module

If you need to uninstall:

### 1. Backup Data (if needed)

```bash
# Export cellphone data
php artisan tinker
>>> Modules\Cellphone\Entities\Cellphone::all()->toJson()
```

### 2. Remove Module

```bash
# Remove module directory
rm -rf Modules/Cellphone

# Remove module version from system table
php artisan tinker
>>> App\System::removeProperty('cellphone_version')

# Clear caches
php artisan config:clear
php artisan cache:clear
composer dump-autoload
```

## ✅ Next Steps

After successful installation:

1. **Configure Permissions**: Assign permissions to user roles
2. **Customize Settings**: Edit module configuration
3. **Add Cellphones**: Start adding inventory
4. **Train Users**: Show your team how to use the module

---

For more information, see:
- [Configuration Guide](configuration.md)
- [Troubleshooting Guide](troubleshooting.md)
- [Main README](../README.md)
