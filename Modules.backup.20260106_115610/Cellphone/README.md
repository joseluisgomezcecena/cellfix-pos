# Cellphone Module for UltimatePOS

## Overview
This module provides specialized management for cellphone/mobile device inventory in a POS system designed for cellphone repair companies and accessory sellers.

## Features

### Core Functionality
- ✅ **IMEI Management**: 15-digit IMEI validation and duplicate checking
- ✅ **Brand & Model Tracking**: Marca and Modelo fields for product categorization
- ✅ **Warranty Integration**: Pre-configured 3, 6, and 12-month warranty options
- ✅ **Condition Tracking**: Estado field (Nuevo/Usado/Reacondicionado)
- ✅ **Physical Location**: Ubicación field for rack/shelf tracking
- ✅ **Search & Filters**: Advanced filtering by brand, model, IMEI, condition, warranty
- ✅ **Dashboard Widget**: Statistics showing inventory by brand and condition
- ✅ **Permissions System**: Granular permissions (create, view, update, delete, export)

### Technical Implementation

**Using Existing Database Structure** (No Migrations Required!)
- Extends the `Product` model
- Maps cellphone fields to `product_custom_field1-5`:
  - `marca` → `product_custom_field1`
  - `modelo` → `product_custom_field2`
  - `ubicacion` → `product_custom_field3`
  - `estado` → `product_custom_field4`
  - `observaciones` → `product_custom_field5`
- IMEI stored in `sku` field (with validation)
- Warranty uses existing `warranty_id` field
- Color/Capacidad can use existing variation system

## Installation

The module is already created and enabled. To complete setup:

### 1. Seed Warranties
```bash
php artisan db:seed --class="Modules\Cellphone\Database\Seeders\WarrantySeeder"
```

This creates:
- Garantía 3 Meses
- Garantía 6 Meses
- Garantía 12 Meses

### 2. Assign Permissions
Go to **Settings → Roles** and assign cellphone permissions to appropriate roles:
- `cellphone.create` - Create new cellphone products
- `cellphone.view` - View cellphone list
- `cellphone.update` - Edit cellphone details
- `cellphone.delete` - Delete cellphones
- `cellphone.export` - Export reports

## Usage

### Access the Module
After logging in, you'll see **"Equipos Celulares"** in the sidebar menu with:
- **Nuevo Equipo** - Add new cellphone
- **Todos los Equipos** - View all cellphones
- **Reporte** - Export reports

### Adding a Cellphone
1. Click "Nuevo Equipo"
2. Fill in required fields:
   - **IMEI**: 15-digit IMEI number (validated for format and duplicates)
   - **Marca**: Brand (e.g., Samsung, Apple, Xiaomi)
   - **Modelo**: Model (e.g., Galaxy S21, iPhone 13)
   - **Estado**: Condition (Nuevo/Usado/Reacondicionado)
   - **Garantía**: Select warranty period
   - **Ubicación**: Physical location/rack
   - **Precio**: Selling price
3. Click Save

### Searching & Filtering
Use the filter bar to search by:
- Marca (Brand)
- Modelo (Model)
- IMEI
- Estado (Condition)
- Garantía (Warranty)

## Configuration

### Field Mapping
Edit `/Modules/Cellphone/Config/config.php` to customize:

```php
'field_mapping' => [
    'marca' => 'product_custom_field1',
    'modelo' => 'product_custom_field2',
    'ubicacion' => 'product_custom_field3',
    'estado' => 'product_custom_field4',
    'observaciones' => 'product_custom_field5',
],
```

### Warranty Options
```php
'warranty_options' => [
    ['duration' => 3, 'duration_type' => 'months', 'name' => 'Garantía 3 Meses'],
    ['duration' => 6, 'duration_type' => 'months', 'name' => 'Garantía 6 Meses'],
    ['duration' => 12, 'duration_type' => 'months', 'name' => 'Garantía 12 Meses'],
],
```

### IMEI Validation
```php
'imei_pattern' => '/^[0-9]{15}$/',  // 15 digits exactly
```

### Condition Options
```php
'estado_options' => [
    'nuevo' => 'Nuevo',
    'usado' => 'Usado',
    'reacondicionado' => 'Reacondicionado',
],
```

## File Structure

```
Modules/Cellphone/
├── Config/
│   └── config.php                  # Module configuration & field mapping
├── Database/
│   └── Seeders/
│       └── WarrantySeeder.php      # Seeds warranty options
├── Entities/
│   └── Cellphone.php               # Cellphone model (extends Product)
├── Http/
│   └── Controllers/
│       ├── CellphoneController.php  # CRUD operations
│       └── DataController.php       # POS integration
├── Resources/
│   ├── lang/en/
│   │   └── lang.php                 # Translations
│   └── views/
│       ├── index.blade.php          # Cellphone list view
│       ├── create.blade.php         # Create form (to be completed)
│       ├── edit.blade.php           # Edit form (to be completed)
│       └── dashboard/
│           └── widget.blade.php     # Dashboard widget
└── Routes/
    └── web.php                      # Module routes
```

## Next Steps (To Complete)

1. **Create Form Views**: Complete `create.blade.php` and `edit.blade.php` with cellphone-specific fields
2. **Add Report Controller**: Create `CellphoneReportController` for Excel/PDF exports
3. **Variation Support**: Implement Color and Capacidad variations using the existing product variation system
4. **Advanced Search**: Add full-text search across all fields
5. **Barcode Integration**: Add barcode printing for IMEI labels

## Integration with Existing POS

The module seamlessly integrates with:
- **Products**: Cellphones appear in the regular product list (filterable)
- **Sales**: Can be sold through normal POS transactions
- **Inventory**: Uses existing stock management
- **Warranties**: Uses existing warranty system
- **Locations**: Compatible with multi-location inventory

## Advantages of This Approach

✅ **No Database Changes**: Uses existing product table structure
✅ **Backward Compatible**: Doesn't affect existing products
✅ **Full POS Integration**: Works with sales, inventory, reports
✅ **IMEI Validation**: Prevents duplicate IMEI entries
✅ **Warranty Tracking**: Leverages existing warranty infrastructure
✅ **Scalable**: Can easily add more fields via custom_field6-20
✅ **Quick Deployment**: No migrations to run

## Support

For issues or questions about this module, check:
- Module configuration: `Modules/Cellphone/Config/config.php`
- Language strings: `Modules/Cellphone/Resources/lang/en/lang.php`
- Permissions: Settings → Roles in POS

## Version
- **Module Version**: 1.0
- **Compatible with**: UltimatePOS (Laravel 9.x)
- **Created**: 2025-10-01
