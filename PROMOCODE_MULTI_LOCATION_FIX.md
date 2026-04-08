# PromoCode Multi-Location Fix - PRODUCTION

## Date: January 16, 2026
## Environment: pos.celfix.mx (PRODUCTION)

## Problem
Promo codes were only available at locations within the same business. For example:
- **Business 1** locations (Celfix Americas, CelFix Nuevo Mexicali, etc.) could NOT use any promo codes
- **Business 2** locations (Sucursal Americas, Sucursal Nuevo Mexicali, etc.) could use the 7 promo codes

This caused promo codes to NOT work across all locations as intended.

## Root Cause
The POS promo code modal was filtering promo companies by `business_id`:

**File:** `/Modules/PromoCode/Resources/views/pos/promo_code_modal.blade.php` (Line 37)

```php
// BEFORE (Line 37-40):
$promo_companies = \Modules\PromoCode\Entities\PromoCompany::where('business_id', session('user.business_id'))
    ->where('is_active', 1)
    ->orderBy('company_name')
    ->pluck('company_name', 'company_name');
```

This meant only promo codes created for the current user's business were shown in the dropdown.

## Solution Implemented

### File Modified: `promo_code_modal.blade.php`

**Change:** Removed `business_id` filter from promo company query

```php
// AFTER (Line 37-40):
// Load all active promo companies across all locations
$promo_companies = \Modules\PromoCode\Entities\PromoCompany::where('is_active', 1)
    ->orderBy('company_name')
    ->pluck('company_name', 'company_name');
```

### Backup Created
**Backup File:** `/Modules/PromoCode/Resources/views/pos/promo_code_modal.blade.php.backup.20260116_230853`

## Current Active Promo Companies in Production

All 7 promo companies now work at ALL locations:

| ID | Company Name | Discounts |
|----|--------------|-----------|
| 1 | UABC Universidad Autónoma de Baja California | Equipos: $100, Pantallas: $100, Accesorios: 10%, Desbloqueos: $50 |
| 2 | ECO MANAGEMENT | Equipos: $100, Pantallas: $100, Accesorios: 10%, Desbloqueos: $50 |
| 3 | CETYS Centro de Enseñanza Técnica y Superior | Equipos: $100, Pantallas: $100, Accesorios: 10%, Desbloqueos: $50 |
| 4 | TIMSA Tecnologías Internacionales de Manufactura | Equipos: $100, Pantallas: $100, Accesorios: 10%, Desbloqueos: $50 |
| 5 | ARCO GAS Octane Systems | Equipos: $100, Pantallas: $100, Accesorios: 10%, Desbloqueos: $50 |
| 6 | POLICIA MEXICALI | Equipos: $100, Pantallas: $100, Accesorios: 10%, Desbloqueos: $50 |
| 7 | EPICENTRO COWORKING SPACE | Equipos: $100, Pantallas: $100, Accesorios: 10%, Desbloqueos: $50 |

**Standard Discount Structure:**
- **Equipos**: $100 fixed discount
- **Pantallas**: $100 fixed discount
- **Accesorios**: 10% percentage discount
- **Desbloqueos**: $50 fixed discount

## Production Locations

### Business 1 Locations (Now Have Access to All Promo Codes)
- Celfix Americas (BL1000-D)
- CelFix Nuevo Mexicali (BL0003)
- Celfix Villa Fontana (BL0004)
- Celfix Benito Juarez (BL0005)

### Business 2 Locations (Continue to Have Access to All Promo Codes)
- Sucursal Americas (CL0001)
- Sucursal Nuevo Mexicali (CL0002)
- Sucursal Villa Fontana (CL0003)
- Sucursal Benito Juárez (CL0004)
- Almacen Equipos (CL0006)

## Result

✅ **All 7 promo codes are now available at ALL 9 locations**, regardless of business_id:

- ✅ Celfix Americas (business 1) - Can now use all 7 promo codes
- ✅ CelFix Nuevo Mexicali (business 1) - Can now use all 7 promo codes
- ✅ Celfix Villa Fontana (business 1) - Can now use all 7 promo codes
- ✅ Celfix Benito Juarez (business 1) - Can now use all 7 promo codes
- ✅ Sucursal Americas (business 2) - Can use all 7 promo codes
- ✅ Sucursal Nuevo Mexicali (business 2) - Can use all 7 promo codes
- ✅ Sucursal Villa Fontana (business 2) - Can use all 7 promo codes
- ✅ Sucursal Benito Juárez (business 2) - Can use all 7 promo codes
- ✅ Almacen Equipos (business 2) - Can use all 7 promo codes

## Testing Instructions

### Test Promo Code at Any Location

1. Log into POS at **any location** (e.g., Celfix Americas, Sucursal Nuevo Mexicali, etc.)
2. Add products to cart (preferably from different categories: equipos, pantallas, accesorios, desbloqueos)
3. Click **"Apply Promo Code"** button (located below cart totals)
4. Select from dropdown - you should see **all 7 promo companies**:
   - ARCO GAS Octane Systems
   - CETYS Centro de Enseñanza Técnica y Superior
   - ECO MANAGEMENT
   - EPICENTRO COWORKING SPACE
   - POLICIA MEXICALI
   - TIMSA Tecnologías Internacionales de Manufactura
   - UABC Universidad Autónoma de Baja California
5. Click **"Apply"**
6. Verify discounts are applied:
   - Equipos products: $100 discount per unit
   - Pantallas products: $100 discount per unit
   - Accesorios products: 10% discount
   - Desbloqueos products: $50 discount per unit
7. Complete sale and verify transaction records correctly

### Expected Behavior

- All active promo codes appear in dropdown regardless of location
- Discounts apply correctly based on product category
- Sales are recorded with proper promo company tracking in `promo_code_usage` table
- Discounts are visible in sale totals

## Cache Cleared

```bash
cd /www/wwwroot/pos.celfix.mx
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

All caches cleared successfully on production.

## Files Modified in Production

1. **`/Modules/PromoCode/Resources/views/pos/promo_code_modal.blade.php`** (Line 37-40)
   - Removed `business_id` filter from promo company dropdown query
   - Added comment explaining change

## Backup Location

**Original File Backup:**
`/Modules/PromoCode/Resources/views/pos/promo_code_modal.blade.php.backup.20260116_230853`

## Rollback Instructions

If you need to revert to business-specific promo codes:

```bash
cd /www/wwwroot/pos.celfix.mx/Modules/PromoCode/Resources/views/pos/
cp promo_code_modal.blade.php.backup.20260116_230853 promo_code_modal.blade.php
cd /www/wwwroot/pos.celfix.mx
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Important Notes

### Admin Panel Still Filters by Business

The admin panel (`/promo-codes/promo-companies`) **still filters** promo companies by business_id for administrative purposes. This is intentional:

- Business 1 admins see and manage their promo codes
- Business 2 admins see and manage their promo codes
- But at POS, **all** promo codes work everywhere

### Database Structure

The `promo_companies` table retains the `business_id` column for administrative organization, but it does NOT restrict discount application at POS.

### Production Database
- **Database Name**: pos_celfix_mx
- **Promo Companies Table**: promo_companies
- **Category Discounts Table**: promo_category_discounts
- **Usage Tracking Table**: promo_code_usage

---

**Status:** ✅ **APPLIED TO PRODUCTION**
**Environment:** pos.celfix.mx (PRODUCTION)
**Impact:** All 9 locations can now use any of the 7 active promo codes
**Breaking Changes:** None
**Testing Required:** Yes - Test at Business 1 locations to confirm they can now access all promo codes
