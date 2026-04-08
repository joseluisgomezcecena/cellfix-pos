#!/bin/bash

##############################################################################
# Cellphone Module - Deploy from Dev to Production (Same Server)
# Run this script from the dev directory with root access
##############################################################################

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Cellphone Module Deployment Script${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Configuration - ADJUST THESE PATHS IF NEEDED
DEV_PATH="/www/wwwroot/dev.celfix.mx"
PROD_PATH="/www/wwwroot/celfix.mx"  # ADJUST THIS TO YOUR PRODUCTION PATH

echo -e "${YELLOW}Configuration:${NC}"
echo "Dev Path:  $DEV_PATH"
echo "Prod Path: $PROD_PATH"
echo ""

# Check if production path exists
if [ ! -d "$PROD_PATH" ]; then
    echo -e "${RED}ERROR: Production path does not exist: $PROD_PATH${NC}"
    echo "Please update PROD_PATH variable in this script to your actual production path"
    exit 1
fi

# Step 1: Create backup of production Cellphone module
echo -e "${YELLOW}Step 1: Creating backup of production Cellphone module...${NC}"
BACKUP_NAME="Cellphone.backup.$(date +%Y%m%d_%H%M%S)"
cp -r "$PROD_PATH/Modules/Cellphone" "$PROD_PATH/Modules/$BACKUP_NAME"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Backup created: $PROD_PATH/Modules/$BACKUP_NAME${NC}"
else
    echo -e "${RED}✗ Backup failed!${NC}"
    exit 1
fi
echo ""

# Step 2: Copy modified files
echo -e "${YELLOW}Step 2: Copying modified files to production...${NC}"

# File 1: Language file
echo "  → Copying lang.php..."
cp "$DEV_PATH/Modules/Cellphone/Resources/lang/en/lang.php" \
   "$PROD_PATH/Modules/Cellphone/Resources/lang/en/lang.php"

if [ $? -eq 0 ]; then
    echo -e "    ${GREEN}✓ lang.php copied${NC}"
else
    echo -e "    ${RED}✗ lang.php failed${NC}"
fi

# File 2: Edit view
echo "  → Copying edit.blade.php..."
cp "$DEV_PATH/Modules/Cellphone/Resources/views/edit.blade.php" \
   "$PROD_PATH/Modules/Cellphone/Resources/views/edit.blade.php"

if [ $? -eq 0 ]; then
    echo -e "    ${GREEN}✓ edit.blade.php copied${NC}"
else
    echo -e "    ${RED}✗ edit.blade.php failed${NC}"
fi

# File 3: Controller
echo "  → Copying CellphoneController.php..."
cp "$DEV_PATH/Modules/Cellphone/Http/Controllers/CellphoneController.php" \
   "$PROD_PATH/Modules/Cellphone/Http/Controllers/CellphoneController.php"

if [ $? -eq 0 ]; then
    echo -e "    ${GREEN}✓ CellphoneController.php copied${NC}"
else
    echo -e "    ${RED}✗ CellphoneController.php failed${NC}"
fi
echo ""

# Step 3: Set correct permissions
echo -e "${YELLOW}Step 3: Setting correct permissions...${NC}"
chown -R www-data:www-data "$PROD_PATH/Modules/Cellphone/Resources/"
chown -R www-data:www-data "$PROD_PATH/Modules/Cellphone/Http/"
chmod 644 "$PROD_PATH/Modules/Cellphone/Resources/lang/en/lang.php"
chmod 644 "$PROD_PATH/Modules/Cellphone/Resources/views/edit.blade.php"
chmod 644 "$PROD_PATH/Modules/Cellphone/Http/Controllers/CellphoneController.php"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Permissions set${NC}"
else
    echo -e "${YELLOW}⚠ Warning: Could not set permissions (may need to run as root)${NC}"
fi
echo ""

# Step 4: Clear Laravel caches
echo -e "${YELLOW}Step 4: Clearing Laravel caches...${NC}"
cd "$PROD_PATH"

php artisan cache:clear > /dev/null 2>&1
echo -e "  ${GREEN}✓ Cache cleared${NC}"

php artisan config:clear > /dev/null 2>&1
echo -e "  ${GREEN}✓ Config cache cleared${NC}"

php artisan route:clear > /dev/null 2>&1
echo -e "  ${GREEN}✓ Route cache cleared${NC}"

php artisan view:clear > /dev/null 2>&1
echo -e "  ${GREEN}✓ View cache cleared${NC}"

echo ""

# Summary
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✓ Deployment Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "Files deployed:"
echo "  ✓ lang.php"
echo "  ✓ edit.blade.php"
echo "  ✓ CellphoneController.php"
echo ""
echo "Backup created at:"
echo "  $PROD_PATH/Modules/$BACKUP_NAME"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Test the Cellphone module in production"
echo "2. Edit a cellphone and verify Stock & Pricing section appears"
echo "3. Test stock adjustment and price update"
echo ""
echo -e "${YELLOW}Rollback (if needed):${NC}"
echo "  rm -rf $PROD_PATH/Modules/Cellphone"
echo "  mv $PROD_PATH/Modules/$BACKUP_NAME $PROD_PATH/Modules/Cellphone"
echo "  cd $PROD_PATH && php artisan cache:clear"
echo ""
