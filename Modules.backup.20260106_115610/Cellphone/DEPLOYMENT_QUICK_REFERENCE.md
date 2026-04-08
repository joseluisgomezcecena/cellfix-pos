# Quick Deployment Reference

## Files to Deploy (3 files)

```
✏️ Modules/Cellphone/Resources/lang/en/lang.php
✏️ Modules/Cellphone/Resources/views/edit.blade.php
✏️ Modules/Cellphone/Http/Controllers/CellphoneController.php
```

## Quick Deploy Commands

### 1. Backup Production
```bash
ssh user@production
cd /path/to/production/celfix
cp -r Modules/Cellphone Modules/Cellphone.backup.$(date +%Y%m%d)
```

### 2. Transfer Files (from dev server)
```bash
scp /www/wwwroot/dev.celfix.mx/Modules/Cellphone/Resources/lang/en/lang.php \
    user@production:/path/to/production/celfix/Modules/Cellphone/Resources/lang/en/lang.php

scp /www/wwwroot/dev.celfix.mx/Modules/Cellphone/Resources/views/edit.blade.php \
    user@production:/path/to/production/celfix/Modules/Cellphone/Resources/views/edit.blade.php

scp /www/wwwroot/dev.celfix.mx/Modules/Cellphone/Http/Controllers/CellphoneController.php \
    user@production:/path/to/production/celfix/Modules/Cellphone/Http/Controllers/CellphoneController.php
```

### 3. Clear Caches (on production)
```bash
ssh user@production
cd /path/to/production/celfix
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear
```

### 4. Set Permissions (on production)
```bash
cd /path/to/production/celfix/Modules/Cellphone
chown -R www-data:www-data Resources/ Http/
chmod 644 Resources/lang/en/lang.php Resources/views/edit.blade.php Http/Controllers/CellphoneController.php
```

## Rollback (if needed)
```bash
ssh user@production
cd /path/to/production/celfix
rm -rf Modules/Cellphone
mv Modules/Cellphone.backup.YYYYMMDD Modules/Cellphone
php artisan cache:clear
```

## Test After Deployment
1. Login to production Celfix
2. Go to Equipos Celulares → Edit any phone
3. Verify "Stock & Pricing" section appears
4. Test adding 5 units to stock
5. Test updating price
6. Verify phone still visible in module list

## No Database Changes Required ✅
This update uses existing tables only. No migrations needed!
