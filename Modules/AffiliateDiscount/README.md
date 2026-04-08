# AffiliateDiscount Module

Complete module for managing affiliated business discounts with automatic application in POS by product category.

## Module Status

✅ **CORE STRUCTURE COMPLETE** - Ready for customization and implementation

### What's Been Created:

#### ✅ Complete Files:
- `module.json` - Module configuration
- `composer.json` - Composer dependencies
- `Config/config.php` - Module settings
- **4 Database Migrations**:
  - `affiliated_businesses` table
  - `affiliated_discount_options` table
  - `pos_session_discounts` table
  - `transaction_affiliate_discounts` table
- **3 Models/Entities**:
  - `AffiliatedBusiness.php` - Main business model
  - `AffiliatedDiscountOption.php` - Discount options model
  - `TransactionAffiliateDiscount.php` - Transaction logging model
- **2 Service Providers**:
  - `AffiliateDiscountServiceProvider.php` - Main provider
  - `RouteServiceProvider.php` - Routes provider
- **Routes**:
  - `web.php` - Admin routes
  - `api.php` - POS API routes
- **2 Controllers**:
  - `InstallController.php` - Installation controller
  - `DataController.php` - Required data controller

#### 🔨 To Be Implemented:
- **2 More Controllers**:
  - `AffiliateDiscountController.php` - Admin CRUD
  - `PosAffiliateController.php` - POS API
- **View Composer**: `PosAffiliateComposer.php`
- **Admin Views**: index, create, edit, discount_options
- **POS Modal View**: discount_modal.blade.php
- **JavaScript**: Modal interactions & calculator
- **CSS**: Styling

---

## Installation

### 1. Install Module
1. Zip the `Modules/AffiliateDiscount` folder
2. Go to `/manage-modules`
3. Click "Upload Module"
4. Select the ZIP file
5. Click "Install"

### 2. Run Migrations
The module will automatically run migrations on installation, creating 4 tables:
- `affiliated_businesses`
- `affiliated_discount_options`
- `pos_session_discounts`
- `transaction_affiliate_discounts`

### 3. Grant Permissions
Assign these permissions to users:
- `manage_affiliate_discounts` - Access admin panel
- `use_affiliate_discounts_in_pos` - Use in POS
- `view_affiliate_reports` - View reports
- `add_discount_options_from_pos` - Quick add from POS

---

## Database Schema

### affiliated_businesses
Stores affiliated business contacts (business-type only).
- `id`, `contact_id`, `business_id`, `is_active`, `notes`, `created_by`, `timestamps`

### affiliated_discount_options
Stores discount options per business per category.
- `id`, `affiliated_business_id`, `category_name` (enum), `discount_type` (enum), `discount_value`, `discount_label`, `is_active`, `display_order`, `timestamps`

### pos_session_discounts
Temporary storage for active discount selections during POS session.
- `id`, `session_id`, `affiliated_business_id`, `selected_discount_id`, `created_at`

### transaction_affiliate_discounts
Final record of discounts applied to completed transactions.
- `id`, `transaction_id`, `affiliated_business_id`, `discount_option_id`, `category_name`, `items_affected`, `total_discount_applied`, `created_at`

---

## Features

### Admin Panel (`/affiliate-discounts`)

**Manage Affiliated Businesses:**
- Add new affiliated business (select from business-type contacts)
- Edit/delete businesses
- Activate/deactivate
- Manage discount options per business

**Discount Options:**
- Create category-specific discounts (EQUIPOS, PANTALLAS, ACCESORIOS, DESBLOQUEOS, SERVICIOS, REPARACIONES)
- Set discount type: Fixed Amount or Percentage
- Custom labels (e.g., "$100 PESOS", "10% DESC.")
- Reorder options
- Activate/deactivate per option

**Reports:**
- Sales by affiliated business
- Discount usage by category
- Date range filtering
- Export to Excel/PDF

### POS Integration (`/pos/create`)

**"Descuento Afiliados" Button:**
- Opens modal when clicked
- Dropdown to select affiliated business
- Shows discount options grouped by category
- Radio buttons for single selection per category
- "+" button to add new options on-the-fly
- Save/Cancel buttons

**Auto-Apply Logic:**
1. When business selected → load discount rules
2. As cart updates → match products to categories
3. Apply selected discounts automatically
4. Update cart totals in real-time
5. On transaction complete → log to database

---

## API Endpoints

### Admin Routes
```
GET    /affiliate-discounts                    List all
GET    /affiliate-discounts/create             Show create form
POST   /affiliate-discounts                    Store new
GET    /affiliate-discounts/{id}/edit          Show edit form
PUT    /affiliate-discounts/{id}               Update
DELETE /affiliate-discounts/{id}               Delete
GET    /affiliate-discounts/{id}/options       Manage discount options
POST   /affiliate-discounts/{id}/options       Save new option
PUT    /affiliate-discounts/options/{optionId} Update option
DELETE /affiliate-discounts/options/{optionId} Delete option
GET    /affiliate-discounts/reports            Reports page
```

### POS API Routes
```
GET    /api/affiliate-discount/businesses         List active businesses
GET    /api/affiliate-discount/options/{businessId} Get options by business
POST   /api/affiliate-discount/save-selection     Save selected discounts
POST   /api/affiliate-discount/calculate          Calculate cart discounts
POST   /api/affiliate-discount/add-option         Quick add option
GET    /api/affiliate-discount/active-session     Get session discounts
DELETE /api/affiliate-discount/clear-session      Clear session
POST   /api/affiliate-discount/save-transaction   Save final transaction
```

---

## Configuration (`Config/config.php`)

```php
'categories' => [
    'equipos' => 'EQUIPOS',
    'pantallas' => 'PANTALLAS',
    'accesorios' => 'ACCESORIOS',
    'desbloqueos' => 'DESBLOQUEOS',
    'servicios' => 'SERVICIOS',
    'reparaciones' => 'REPARACIONES',
],

'session_key' => 'affiliate_discount_selections',
'cache_ttl' => 3600,
```

---

## Next Steps for Complete Implementation

### 1. Create Admin Controller (`AffiliateDiscountController.php`)
Implement CRUD methods for managing businesses and discount options.

### 2. Create POS API Controller (`PosAffiliateController.php`)
Implement API methods for:
- Getting businesses and options
- Saving selections to session
- Calculating discounts
- Logging transactions

### 3. Create View Composer (`PosAffiliateComposer.php`)
Inject affiliate data into POS views.

### 4. Create Admin Views
- `admin/index.blade.php` - List businesses
- `admin/create.blade.php` - Add business
- `admin/edit.blade.php` - Edit business
- `admin/discount_options.blade.php` - Manage options

### 5. Create POS Modal (`pos/discount_modal.blade.php`)
Based on screenshot design.

### 6. Create JavaScript
- `affiliate-discount-modal.js` - Modal interactions
- `affiliate-discount-calculator.js` - Auto-calculation logic

### 7. Create CSS (`affiliate-discount.css`)
Style the modal and admin pages.

---

## Usage Example

### Admin Setup:
1. Go to `/affiliate-discounts`
2. Click "Add New"
3. Select business-type contact (e.g., "Skyworks")
4. Click "Manage Discount Options"
5. Add options:
   - EQUIPOS: $100 PESOS (fixed)
   - PANTALLAS: $100 PESOS (fixed)
   - ACCESORIOS: 10% DESC. (percentage)
   - DESBLOQUEOS: $50 PESOS (fixed)
6. Save and activate

### POS Usage:
1. Open POS (`/pos/create`)
2. Click "Descuento Afiliados" button
3. Select "Skyworks" from dropdown
4. Select desired discounts per category
5. Click "Guardar"
6. Add products to cart
7. Discounts apply automatically by category
8. Complete sale - discounts logged to database

---

## Support

For issues or questions:
- Email: admin@daseo.co
- Module Version: 1.0

---

## License

MIT License
