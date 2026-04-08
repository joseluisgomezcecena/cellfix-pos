# Affiliate Discount Module - Fix Applied

## Date: 2025-10-17

## Problem Identified

The affiliate discount module was not applying discounts in the POS system for products in the "accesorios" category.

### Root Cause

**Category Name Mismatch**: The discount options were configured for the category name `'accesorios'`, but the actual product categories in the database had different names:
- Protectores
- Protector
- Cargadores
- Bocinas

The `mapProductCategory()` function in `PosAffiliateController.php` was trying to match these actual category names against hardcoded values like "accesorios", "equipos", "pantallas", etc. Since there was no mapping for "Protectores" → "accesorios", the discount calculation would:
1. Get the product category "Protectores" from the database
2. Try to map it to a discount category
3. Find no match
4. Skip discount application

## Solution Applied

Updated the `mapProductCategory()` method in `Modules/AffiliateDiscount/Http/Controllers/PosAffiliateController.php` (line 338-383) to include mappings for the actual database category names.

### Changes Made

1. **Updated Category Mapping** - Added actual database category names:
```php
$mapping = [
    // Direct discount category names
    'equipos' => 'equipos',
    'pantallas' => 'pantallas',
    'accesorios' => 'accesorios',
    'desbloqueos' => 'desbloqueos',
    'servicios' => 'servicios',
    'reparaciones' => 'reparaciones',

    // Actual database categories mapped to accesorios
    'protectores' => 'accesorios',
    'protector' => 'accesorios',
    'cargadores' => 'accesorios',
    'bocinas' => 'accesorios',
    'audifonos' => 'accesorios',
    'audífonos' => 'accesorios',
    'cables' => 'accesorios',
    'adaptadores' => 'accesorios',
    'soportes' => 'accesorios',
    'micas' => 'accesorios',
    'fundas' => 'accesorios',
    'carcasas' => 'accesorios',
];
```

2. **Enhanced Logging** - Added comprehensive debug logging in `calculateDiscounts()` method:
   - Session information logging
   - Product category mapping logging
   - Discount calculation details
   - Error tracking with context

3. **Improved JavaScript Logging** - Enhanced `affiliate-discount-calculator.js`:
   - Added total items count logging
   - Added subtotal calculation in console output
   - Better visibility into cart extraction process

## Current Database Configuration

### Affiliated Business
- **ID**: 1
- **Name**: Integradora de negocios mixtos
- **Status**: Active

### Discount Options
- **ID 1**: Category "accesorios", 10% discount, Active
- **ID 2**: Category "accesorios", 12% discount, Active

### Product Categories in Use
- Protectores (will now map to accesorios)
- Protector (will now map to accesorios)
- Cargadores (will now map to accesorios)
- Bocinas (will now map to accesorios)

## How It Works Now

1. **User selects affiliate discount** in POS modal
2. **User adds product** (e.g., "Silicone Case iPhone XR" with category "Protector")
3. **Backend receives calculation request** with product_id
4. **Query database** to get product category: "Protector"
5. **Map category**: "protector" → "accesorios" ✅
6. **Find discount options** for "accesorios" category
7. **Calculate discount**: Apply 10% or 12% discount
8. **Update POS discount field** automatically
9. **Complete transaction** with discount saved

## Testing the Fix

### Manual Test Steps

1. **Login to POS**: https://dev.celfix.mx/login
   - Username: `daseoadmin`
   - Password: `fi32y49pf8queiwhjoasjd`

2. **Open POS**: Navigate to https://dev.celfix.mx/pos/create

3. **Open Discount Modal**: Click "Descuento Afiliados" button

4. **Select Business**: Choose "Integradora de negocios mixtos"

5. **Select Discount**: Choose either 10% or 12% discount for accesorios category

6. **Add Product**: Add any product with category "Protectores", "Cargadores", or "Bocinas"

7. **Verify**: Check that discount is automatically applied to the cart total

8. **Check Console**: Open browser DevTools and check for log messages:
   ```
   [Affiliate Discount] Extracted item: {product_id: 33, quantity: 1, unit_price: 150, subtotal: 150}
   [Affiliate Discount] Total items extracted: 1
   [Affiliate Discount] Calculation response: {success: true, data: {...}}
   [Affiliate Discount] Set discount_amount to: 15.00
   ```

9. **Check Laravel Logs**: Check `storage/logs/laravel.log` for backend logs:
   ```
   [AffiliateDiscount] Product categories from DB: {33: "Protector"}
   [AffiliateDiscount] Category mapping: {product_id: 33, product_category: "Protector", mapped_to_discount_category: "accesorios"}
   [AffiliateDiscount] Discount calculated: {product_id: 33, category: "accesorios", subtotal: 150, discount: 15}
   ```

### Expected Results

- ✅ Discount modal opens successfully
- ✅ Business "Integradora de negocios mixtos" appears in dropdown
- ✅ Discount options for accesorios are visible
- ✅ When products are added, discount is automatically calculated
- ✅ Discount amount appears in the POS discount field
- ✅ Cart total reflects the discount
- ✅ Console logs show successful discount calculation
- ✅ Laravel logs show category mapping and discount calculation

## Maintenance Notes

### Adding More Category Mappings

If you need to add more product categories in the future, edit the `mapProductCategory()` method in:
`Modules/AffiliateDiscount/Http/Controllers/PosAffiliateController.php`

Add new entries to the `$mapping` array:
```php
'new_category_name' => 'discount_category',
```

For example, if you add a category "Vidrios Templados" that should get accesorios discounts:
```php
'vidrios templados' => 'accesorios',
'vidrio templado' => 'accesorios',
```

### Checking Logs

**Browser Console Logs**: Press F12 → Console tab
**Laravel Logs**: `tail -f /www/wwwroot/dev.celfix.mx/storage/logs/laravel.log`

All affiliate discount logs are prefixed with `[AffiliateDiscount]` or `[Affiliate Discount]` for easy filtering.

### Troubleshooting

If discounts still don't work after this fix:

1. **Check if module is active**: Verify `modules_statuses.json` has `"AffiliateDiscount": true`

2. **Check category names**: Run this query to see actual category names:
   ```sql
   SELECT DISTINCT c.name
   FROM categories c
   JOIN products p ON p.category_id = c.id
   WHERE p.business_id = 1;
   ```

3. **Check discount options**: Verify discount options exist and are active:
   ```sql
   SELECT * FROM affiliated_discount_options WHERE is_active = 1;
   ```

4. **Check session**: The discount selection must be saved to session before adding products

5. **Clear cache**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

## Files Modified

1. `Modules/AffiliateDiscount/Http/Controllers/PosAffiliateController.php`
   - Updated `mapProductCategory()` method (lines 338-383)
   - Enhanced `calculateDiscounts()` method with comprehensive logging (lines 109-195)

2. `Modules/AffiliateDiscount/Resources/assets/js/affiliate-discount-calculator.js`
   - Enhanced `extractCartItems()` method with better logging (lines 95-135)

## Verification

The fix has been applied and is ready for testing. The code changes ensure that:
1. All common accessory category names will map to the "accesorios" discount category
2. Comprehensive logging will help diagnose any future issues
3. The discount calculation process is transparent and debuggable

## Next Steps

1. Test in browser with actual POS workflow
2. Verify console logs show proper category mapping
3. Verify discount is applied to cart
4. Complete a test transaction
5. Verify discount is saved to database in `transaction_affiliate_discounts` table
