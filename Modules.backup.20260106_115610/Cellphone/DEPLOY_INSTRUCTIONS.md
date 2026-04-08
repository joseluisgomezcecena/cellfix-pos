# Deploy Cellphone Module to Production (Same Server)

## Quick Instructions for Claude Code

You're on the same server with both dev and production. Here's how to deploy:

---

## Step 1: Update the Production Path in the Script

First, you need to tell me what your **production path** is. Common options:

- `/www/wwwroot/celfix.mx`
- `/www/wwwroot/www.celfix.mx`
- `/var/www/celfix.mx`
- `/var/www/html/celfix`

**Once you tell me the production path, I'll update the script for you.**

---

## Step 2: Run the Deployment Script

After I update the path, run these commands in Claude Code:

```bash
# Make the script executable
chmod +x /www/wwwroot/dev.celfix.mx/Modules/Cellphone/DEPLOY_TO_PRODUCTION.sh

# Run the deployment script
bash /www/wwwroot/dev.celfix.mx/Modules/Cellphone/DEPLOY_TO_PRODUCTION.sh
```

That's it! The script will:
1. ✅ Backup production Cellphone module
2. ✅ Copy 3 modified files to production
3. ✅ Set correct permissions
4. ✅ Clear all Laravel caches

---

## Alternative: Manual Commands (if you prefer)

If you want to run commands manually instead of using the script:

### 1. First, tell me your production path, then run:

```bash
# Set your production path (REPLACE THIS PATH!)
PROD="/www/wwwroot/celfix.mx"  # <-- UPDATE THIS

# Create backup
cp -r $PROD/Modules/Cellphone $PROD/Modules/Cellphone.backup.$(date +%Y%m%d)

# Copy the 3 files
cp /www/wwwroot/dev.celfix.mx/Modules/Cellphone/Resources/lang/en/lang.php \
   $PROD/Modules/Cellphone/Resources/lang/en/lang.php

cp /www/wwwroot/dev.celfix.mx/Modules/Cellphone/Resources/views/edit.blade.php \
   $PROD/Modules/Cellphone/Resources/views/edit.blade.php

cp /www/wwwroot/dev.celfix.mx/Modules/Cellphone/Http/Controllers/CellphoneController.php \
   $PROD/Modules/Cellphone/Http/Controllers/CellphoneController.php

# Set permissions
chown -R www-data:www-data $PROD/Modules/Cellphone/Resources/
chown -R www-data:www-data $PROD/Modules/Cellphone/Http/

# Clear caches
cd $PROD
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## What I Need From You

**Just tell me your production path!**

Example answers:
- "Production is at `/www/wwwroot/celfix.mx`"
- "Production path is `/var/www/html/celfix`"
- "It's in `/www/wwwroot/www.celfix.mx`"

Once you tell me, I'll:
1. Update the deployment script with the correct path
2. You just run `bash DEPLOY_TO_PRODUCTION.sh`
3. Done! ✅
