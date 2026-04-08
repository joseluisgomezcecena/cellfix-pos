# AffiliateDiscount Module - Integration Guide

## ✅ Module Complete!

All core files have been created. Follow these steps to integrate the module into your POS system.

---

## Step 1: Publish Module Assets

After installation, publish the module assets:

```bash
cd /www/wwwroot/dev.celfix.mx
php artisan module:publish AffiliateDiscount
```

This will copy CSS and JS files to `public/modules/affiliatediscount/`.

---

## Step 2: Integrate POS Modal

The modal needs to be included in the POS views. You have two options:

### Option A: Modify POS View Directly (Not Recommended)

Edit `resources/views/sell/create.blade.php` and add **before the closing `@endsection`**:

```blade
@if(isset($show_affiliate_discount) && $show_affiliate_discount)
    @include('affiliatediscount::pos.discount_modal')
@endif
```

**Location:** Add it near the bottom of the content section, after the main POS form.

### Option B: Use a Hook/Event (Recommended if Available)

If your POS system has a hook system, register a hook to inject the modal:

```php
// In a service provider or hook file
add_action('pos_sidebar_after', function() {
    if (isset($show_affiliate_discount) && $show_affiliate_discount) {
        echo view('affiliatediscount::pos.discount_modal')->render();
    }
});
```

---

## Step 3: Manual Integration Instructions

### For `resources/views/sell/create.blade.php`:

**Find this section** (around line 70-80, in the left sidebar area):

```blade
<div class="col-md-3">
    <!-- Customer selector here -->
    <div class="form-group">
        {!! Form::select('contact_id', ...
    </div>
```

**Add AFTER the customer selector:**

```blade
{{-- Affiliate Discount Button & Modal --}}
@if(isset($show_affiliate_discount) && $show_affiliate_discount)
    @include('affiliatediscount::pos.discount_modal')
@endif
```

---

## Step 4: Test the Installation

### 1. Install the Module

```bash
# Zip the module
cd /www/wwwroot/dev.celfix.mx/Modules
zip -r AffiliateDiscount.zip AffiliateDiscount/

# Then:
# - Go to http://dev.celfix.mx/manage-modules
# - Upload AffiliateDiscount.zip
# - Click "Install"
```

### 2. Grant Permissions

Go to **Users → Roles** and assign permissions:
- `manage_affiliate_discounts` - For admin users
- `use_affiliate_discounts_in_pos` - For POS users
- `view_affiliate_reports` - For managers
- `add_discount_options_from_pos` - Optional: quick add from POS

### 3. Create Test Data

**Add Affiliated Business:**
1. Go to `/affiliate-discounts`
2. Click "Add New"
3. Select a business-type contact (e.g., "Skyworks")
4. Save

**Add Discount Options:**
1. Click "Manage Discount Options"
2. Switch to each category tab (EQUIPOS, PANTALLAS, etc.)
3. Click "+ Add New Option"
4. Fill in:
   - Label: `$100 PESOS`
   - Type: Fixed Amount
   - Value: 100
5. Repeat for other categories

### 4. Test in POS

1. Open POS: `/pos/create`
2. You should see "Descuento Afiliados" button below customer selector
3. Click button → modal opens
4. Select affiliated business
5. Select discount options per category
6. Click "Guardar"
7. Add products to cart
8. Discounts should apply automatically!

---

## Step 5: Customize Category Mapping

Products need to have categories that match the discount categories. Edit the mapping in:

**File:** `Modules/AffiliateDiscount/Http/Controllers/PosAffiliateController.php`

**Method:** `mapProductCategory()` (line ~250)

```php
private function mapProductCategory($productCategory)
{
    $mapping = [
        'equipos' => 'equipos',
        'pantallas' => 'pantallas',
        'accesorios' => 'accesorios',
        // Add your actual product category names here
        'phones' => 'equipos',
        'screens' => 'pantallas',
        'accessories' => 'accesorios',
    ];

    // Match logic...
}
```

---

## Troubleshooting

### Modal Doesn't Appear

**Check:**
1. View composer is registered in `AffiliateDiscountServiceProvider.php`
2. User has `use_affiliate_discounts_in_pos` permission
3. Modal include is added to POS view
4. JavaScript console for errors

**Solution:**
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### Discounts Not Calculating

**Check:**
1. Products have proper category assigned
2. Category mapping in `mapProductCategory()` method
3. JavaScript console for API errors
4. Session is active (check `/api/affiliate-discount/active-session`)

### Assets Not Loading

**Check:**
1. Assets published: `php artisan module:publish AffiliateDiscount`
2. Files exist in `public/modules/affiliatediscount/`
3. Correct paths in modal view

**Republish:**
```bash
php artisan module:publish AffiliateDiscount --force
```

---

## File Structure Summary

```
Modules/AffiliateDiscount/
├── Config/
│   └── config.php ✅
├── Database/
│   └── Migrations/ (4 files) ✅
├── Entities/ (3 models) ✅
├── Http/
│   ├── Controllers/
│   │   ├── AffiliateDiscountController.php ✅
│   │   ├── PosAffiliateController.php ✅
│   │   ├── InstallController.php ✅
│   │   └── DataController.php ✅
│   └── ViewComposers/
│       └── PosAffiliateComposer.php ✅
├── Providers/ (2 files) ✅
├── Resources/
│   ├── views/
│   │   ├── admin/ (4 files) ✅
│   │   ├── pos/
│   │   │   └── discount_modal.blade.php ✅
│   │   ├── reports/
│   │   │   └── affiliated_sales.blade.php ✅
│   │   └── install/
│   │       └── index.blade.php ✅
│   └── assets/
│       ├── js/
│       │   ├── affiliate-discount-modal.js ✅
│       │   └── affiliate-discount-calculator.js ✅
│       └── css/
│           └── affiliate-discount.css ✅
├── Routes/
│   ├── web.php ✅
│   └── api.php ✅
├── module.json ✅
├── composer.json ✅
├── README.md ✅
└── INTEGRATION.md ✅
```

**Total Files Created: 31** ✅

---

## Next Steps

1. ✅ Install module
2. ✅ Publish assets
3. ✅ Integrate modal into POS view
4. ✅ Grant permissions
5. ✅ Add test data
6. ✅ Test in POS
7. Customize category mapping as needed
8. Train users

---

## Support

For issues:
- Check `storage/logs/laravel.log`
- Enable debug mode: `APP_DEBUG=true` in `.env`
- Contact: admin@daseo.co

**Module Version:** 1.0
**Compatible With:** UltimatePOS (Laravel 9+, PHP 8.0+)
