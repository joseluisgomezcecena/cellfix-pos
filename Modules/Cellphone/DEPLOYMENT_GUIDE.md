# Cellphone Module - Stock Management Deployment Guide

## Files Modified

### 1. Language File
**Path**: `Modules/Cellphone/Resources/lang/en/lang.php`
- **What Changed**: Added 25+ translation keys for stock and price management
- **Lines Added**: Lines 112-146 (Stock Management, Price Management, Stock & Pricing Section)

### 2. Controller
**Path**: `Modules/Cellphone/Http/Controllers/CellphoneController.php`
- **What Changed**:
  - Enhanced `update()` method with stock and price management logic
  - Added `convertToFloat()` helper method
- **Lines Modified**:
  - Lines 416-556 (update method enhancement)
  - Lines 577-591 (new helper method)

### 3. Edit View
**Path**: `Modules/Cellphone/Resources/views/edit.blade.php`
- **What Changed**:
  - Replaced read-only stock section with interactive Stock & Pricing management UI
  - Added JavaScript for dynamic location rows and price calculations
- **Lines Modified**:
  - Lines 257-439 (new Stock & Pricing widget)
  - Lines 488-551 (enhanced JavaScript)

### 4. Documentation (New File)
**Path**: `Modules/Cellphone/STOCK_MANAGEMENT_SOLUTION.md`
- **What Changed**: New comprehensive documentation file
- **Purpose**: Reference guide for the new features

## Complete List of Modified Files

```
Modules/Cellphone/
├── Resources/
│   ├── lang/
│   │   └── en/
│   │       └── lang.php                          ✏️ MODIFIED
│   └── views/
│       └── edit.blade.php                         ✏️ MODIFIED
├── Http/
│   └── Controllers/
│       └── CellphoneController.php                ✏️ MODIFIED
├── STOCK_MANAGEMENT_SOLUTION.md                   ✨ NEW FILE
└── DEPLOYMENT_GUIDE.md                            ✨ NEW FILE (this file)
```

**Total Files to Deploy**: 3 modified + 2 documentation files = **5 files**

## Deployment Steps

### Option 1: Manual File Transfer (Recommended for Production)

#### Step 1: Create Backup on Production
```bash
# SSH into production server
ssh user@production-server

# Navigate to production directory
cd /path/to/production/celfix

# Create backup of Cellphone module
cp -r Modules/Cellphone Modules/Cellphone.backup.$(date +%Y%m%d_%H%M%S)

# Verify backup created
ls -la Modules/ | grep Cellphone
```

#### Step 2: Transfer Modified Files from Dev to Production
From your **local machine** or **dev server**:

```bash
# Set production server details
PROD_SERVER="user@production-server"
PROD_PATH="/path/to/production/celfix"

# Transfer the 3 modified files
scp /www/wwwroot/dev.celfix.mx/Modules/Cellphone/Resources/lang/en/lang.php \
    $PROD_SERVER:$PROD_PATH/Modules/Cellphone/Resources/lang/en/lang.php

scp /www/wwwroot/dev.celfix.mx/Modules/Cellphone/Resources/views/edit.blade.php \
    $PROD_SERVER:$PROD_PATH/Modules/Cellphone/Resources/views/edit.blade.php

scp /www/wwwroot/dev.celfix.mx/Modules/Cellphone/Http/Controllers/CellphoneController.php \
    $PROD_SERVER:$PROD_PATH/Modules/Cellphone/Http/Controllers/CellphoneController.php

# Transfer documentation files (optional)
scp /www/wwwroot/dev.celfix.mx/Modules/Cellphone/STOCK_MANAGEMENT_SOLUTION.md \
    $PROD_SERVER:$PROD_PATH/Modules/Cellphone/STOCK_MANAGEMENT_SOLUTION.md

scp /www/wwwroot/dev.celfix.mx/Modules/Cellphone/DEPLOYMENT_GUIDE.md \
    $PROD_SERVER:$PROD_PATH/Modules/Cellphone/DEPLOYMENT_GUIDE.md
```

#### Step 3: Set Correct Permissions on Production
```bash
# SSH into production
ssh user@production-server

# Navigate to Cellphone module
cd /path/to/production/celfix/Modules/Cellphone

# Set correct ownership (adjust user:group as needed)
chown -R www-data:www-data Resources/
chown -R www-data:www-data Http/

# Set correct permissions
chmod 644 Resources/lang/en/lang.php
chmod 644 Resources/views/edit.blade.php
chmod 644 Http/Controllers/CellphoneController.php
```

#### Step 4: Clear Laravel Caches
```bash
# Still on production server
cd /path/to/production/celfix

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# If using OPcache (recommended)
php artisan optimize:clear
```

#### Step 5: Test on Production
1. Login to production Celfix
2. Navigate to **Equipos Celulares**
3. Click **Edit** on any cellphone
4. Verify the new **Stock & Pricing** section appears
5. Test stock adjustment
6. Test price update
7. Verify cellphone still appears in module after update

---

### Option 2: rsync Transfer (For Advanced Users)

If you have rsync access:

```bash
# Sync only the modified files
rsync -avz --progress \
  /www/wwwroot/dev.celfix.mx/Modules/Cellphone/Resources/lang/en/lang.php \
  /www/wwwroot/dev.celfix.mx/Modules/Cellphone/Resources/views/edit.blade.php \
  /www/wwwroot/dev.celfix.mx/Modules/Cellphone/Http/Controllers/CellphoneController.php \
  user@production:/path/to/production/celfix/Modules/Cellphone/

# Then follow Step 3 & 4 above (permissions and cache clearing)
```

---

### Option 3: Git-Based Deployment (If Using Version Control)

If you have the Cellphone module in a Git repository:

```bash
# On dev server: Commit changes
cd /www/wwwroot/dev.celfix.mx/Modules/Cellphone
git add Resources/lang/en/lang.php
git add Resources/views/edit.blade.php
git add Http/Controllers/CellphoneController.php
git add STOCK_MANAGEMENT_SOLUTION.md
git add DEPLOYMENT_GUIDE.md
git commit -m "feat: Add stock and price management to Cellphone module

- Add stock adjustment functionality (add/subtract/set)
- Add price management (purchase/sell prices)
- Add support for adding stock to new locations
- Ensure cellphone flag preservation on all updates
- Add comprehensive UI for stock and pricing management"

git push origin main

# On production server: Pull changes
cd /path/to/production/celfix/Modules/Cellphone
git pull origin main

# Then follow Step 4 (cache clearing)
```

---

## Pre-Deployment Checklist

Before deploying to production, verify:

- [ ] Dev environment tested thoroughly
- [ ] Backup of production Cellphone module created
- [ ] File permissions correct on production
- [ ] Laravel caches will be cleared after deployment
- [ ] Maintenance mode enabled (if doing during business hours)
- [ ] Rollback plan ready (restore from backup if needed)

## Post-Deployment Verification

After deployment, verify these features work:

### 1. Stock Adjustments
- [ ] Edit a cellphone
- [ ] Add 5 units to an existing location
- [ ] Save and verify stock increased
- [ ] Check that cellphone still appears in module list

### 2. Price Updates
- [ ] Edit a cellphone
- [ ] Update purchase price
- [ ] Update sell price
- [ ] Verify sell price inc. tax auto-calculates
- [ ] Save and verify prices updated

### 3. New Location Stock
- [ ] Edit a cellphone
- [ ] Add stock to a new location
- [ ] Save and verify new location appears in stock table

### 4. Flag Preservation
- [ ] Edit a cellphone multiple times
- [ ] Verify it always appears in Cellphone module
- [ ] Verify it appears in POS when stock > 0

### 5. POS Integration
- [ ] Open POS screen
- [ ] Search for the cellphone by IMEI or name
- [ ] Verify it appears in search results
- [ ] Verify stock quantity is correct

## Rollback Procedure

If something goes wrong:

```bash
# SSH into production
ssh user@production-server
cd /path/to/production/celfix

# Find backup
ls -la Modules/ | grep Cellphone.backup

# Restore from backup
rm -rf Modules/Cellphone
mv Modules/Cellphone.backup.YYYYMMDD_HHMMSS Modules/Cellphone

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Database Changes

**Important**: This update does **NOT** require any database migrations or schema changes.

All functionality uses existing database tables:
- `products` (existing)
- `variations` (existing)
- `variation_location_details` (existing)
- `product_locations` (existing pivot)

No new tables, no new columns, no migrations needed!

## Performance Considerations

This update should have **minimal performance impact**:

- No additional database queries on page load
- Stock adjustments use efficient updates (not inserts/deletes)
- All operations wrapped in single transaction
- No N+1 query issues

## Support & Troubleshooting

### Issue: Files not updating after deployment

**Solution**:
```bash
# Clear all Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# If using PHP-FPM, restart it
sudo systemctl restart php8.1-fpm  # Adjust version as needed

# If using Nginx, reload
sudo systemctl reload nginx
```

### Issue: Permissions error

**Solution**:
```bash
# Set correct ownership
sudo chown -R www-data:www-data Modules/Cellphone/

# Set correct permissions
sudo chmod -R 755 Modules/Cellphone/
sudo chmod -R 644 Modules/Cellphone/Resources/
```

### Issue: View not rendering correctly

**Solution**:
```bash
# Clear view cache specifically
php artisan view:clear

# Check blade syntax errors
php artisan view:cache
# If errors appear, check the edit.blade.php file for syntax issues
```

## Maintenance Mode (Optional)

If deploying during business hours:

```bash
# Enable maintenance mode
php artisan down --message="Updating Cellphone module, back in 5 minutes"

# Deploy files

# Clear caches

# Disable maintenance mode
php artisan up
```

## Summary

**What to deploy**: 3 files (lang.php, edit.blade.php, CellphoneController.php)

**Commands to run**:
1. Transfer files to production
2. Set permissions
3. Clear caches (`php artisan cache:clear config:clear route:clear view:clear`)
4. Test functionality

**Time estimate**: 5-10 minutes

**Risk level**: Low (no database changes, easily reversible)

**Rollback time**: 2 minutes (restore from backup)
