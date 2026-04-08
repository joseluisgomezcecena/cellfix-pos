# Configuration Guide

This guide covers configuration options for the POS Cellphone Module.

## 📁 Configuration File

The main configuration file is located at:
```
/Modules/Cellphone/Config/config.php
```

## 🗺️ Field Mapping

The module maps cellphone-specific fields to product custom fields:

```php
'field_mapping' => [
    'marca' => 'product_custom_field1',           // Brand
    'modelo' => 'product_custom_field2',          // Model
    'ubicacion' => 'product_custom_field3',       // Physical location/rack
    'estado' => 'product_custom_field4',          // Condition
    'observaciones' => 'product_custom_field5',   // Additional notes
    'cellphone_flag' => 'product_custom_field6',  // Module identifier flag
],
```

### Customizing Field Mapping

If you need to use different custom fields:

1. Edit `/Modules/Cellphone/Config/config.php`
2. Change the mapping to available custom fields
3. Clear caches: `php artisan config:clear`

## 📱 Cellphone Flag

The module uses a flag to identify cellphone products:

```php
'cellphone_flag_value' => 'CELLPHONE_MODULE',
```

This ensures cellphones are isolated from regular products in the module views.

## 🛡️ Warranty Configuration

Configure available warranty options:

```php
'warranty_options' => [
    ['duration' => 3, 'duration_type' => 'months', 'name' => 'Garantía 3 Meses'],
    ['duration' => 6, 'duration_type' => 'months', 'name' => 'Garantía 6 Meses'],
    ['duration' => 12, 'duration_type' => 'months', 'name' => 'Garantía 12 Meses'],
],
```

### Adding Custom Warranties

```php
'warranty_options' => [
    // Existing warranties...
    ['duration' => 24, 'duration_type' => 'months', 'name' => 'Garantía 24 Meses'],
    ['duration' => 1, 'duration_type' => 'years', 'name' => 'Garantía 1 Año'],
],
```

## 🔢 IMEI Validation

Configure IMEI validation pattern:

```php
'imei_pattern' => '/^[0-9]{15}$/',  // 15 digits exactly
```

### Custom IMEI Patterns

For different IMEI formats:

```php
// Allow 14-17 digits
'imei_pattern' => '/^[0-9]{14,17}$/',

// Allow alphanumeric serial numbers
'imei_pattern' => '/^[A-Z0-9]{10,20}$/',
```

## 📊 Condition Options

Configure device condition options:

```php
'estado_options' => [
    'nuevo' => 'Nuevo',
    'usado' => 'Usado',
    'reacondicionado' => 'Reacondicionado',
],
```

### Adding Custom Conditions

```php
'estado_options' => [
    'nuevo' => 'Nuevo',
    'usado' => 'Usado',
    'reacondicionado' => 'Reacondicionado',
    'refurbished' => 'Refurbished',           // English
    'como_nuevo' => 'Como Nuevo',             // Like New
    'para_repuestos' => 'Para Repuestos',     // For Parts
],
```

## 🎨 Module Settings

### Module Version

```php
'module_version' => '1.0',
```

This version is stored in the system table during installation.

### Product Type

```php
'product_type' => 'single',
```

The module uses 'single' product type with optional variations for color and capacity.

## 🔐 Permissions Configuration

Permissions are defined in `DataController.php`:

```php
public function user_permissions()
{
    return [
        [
            'value' => 'cellphone.create',
            'label' => __('cellphone::lang.create_cellphone'),
            'default' => false
        ],
        [
            'value' => 'cellphone.view',
            'label' => __('cellphone::lang.view_cellphone'),
            'default' => false
        ],
        // ... more permissions
    ];
}
```

### Assigning Permissions

1. Go to **Settings** → **Roles**
2. Select a role to edit
3. Check the cellphone permissions:
   - `cellphone.create` - Create cellphones
   - `cellphone.view` - View cellphone list
   - `cellphone.update` - Edit cellphones
   - `cellphone.delete` - Delete cellphones
   - `cellphone.export` - Export reports

### Recommended Permission Sets

**Manager/Admin:**
- ✅ All cellphone permissions

**Sales Staff:**
- ✅ `cellphone.create`
- ✅ `cellphone.view`
- ✅ `cellphone.update`

**Cashier:**
- ✅ `cellphone.view`

## 🌐 Language Configuration

### Translation Files

Located at `/Modules/Cellphone/Resources/lang/`:

```
lang/
├── en/
│   └── lang.php      # English translations
└── es/
    └── lang.php      # Spanish translations (if added)
```

### Adding Translations

Create `lang/es/lang.php`:

```php
<?php

return [
    'menu' => 'Equipos Celulares',
    'new_cellphone' => 'Nuevo Celular',
    'all_cellphones' => 'Todos los Celulares',
    'create_cellphone' => 'Crear Celular',
    // ... more translations
];
```

## 🎯 Menu Configuration

The menu is configured in `DataController.php`:

```php
public function modifyAdminMenu()
{
    return Menu::modify('admin-sidebar-menu', function ($menu) {
        $menu->dropdown(
            __('cellphone::lang.menu'),
            function ($sub) {
                $sub->url(
                    action('\Modules\Cellphone\Http\Controllers\CellphoneController@create'),
                    __('cellphone::lang.new_cellphone'),
                    ['active' => request()->segment(1) == 'cellphone' && request()->segment(2) == 'create']
                );
                // ... more menu items
            },
            ['icon' => 'fa fa-mobile', 'id' => 'cellphone-menu']
        )->order(33);
    });
}
```

### Customizing Menu

**Change menu order:**
```php
->order(25);  // Lower number = higher in menu
```

**Change icon:**
```php
['icon' => 'fa fa-phone', 'id' => 'cellphone-menu']
```

## 📈 Dashboard Widget

Configure the dashboard widget in `DataController.php`:

```php
public function dashboard_widgets($business_id)
{
    $widgets = [];

    if (auth()->user()->can('cellphone.view')) {
        $widgets['cellphone_summary'] = [
            'label' => __('cellphone::lang.dashboard_widget_title'),
            'view' => 'cellphone::dashboard.widget',
            'data' => $this->getDashboardData($business_id)
        ];
    }

    return $widgets;
}
```

## 🏢 Multi-Location Configuration

The module supports multi-location setups out of the box. Cellphones automatically respect:

- Business location filters
- Location-based permissions
- Multi-location inventory

No additional configuration needed.

## 🔄 Cache Configuration

### Clearing Module Cache

After configuration changes:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Disabling Cache During Development

In `.env`:
```
APP_DEBUG=true
CACHE_DRIVER=array
```

## 📝 Logging Configuration

Module logs are written to:
- `storage/logs/laravel.log` - General logs
- `storage/logs/cellphone-install.log` - Installation logs

### Enable Debug Logging

In `.env`:
```
APP_DEBUG=true
APP_LOG_LEVEL=debug
```

## 🔧 Advanced Configuration

### Custom Validation Rules

Edit form requests to add custom validation:

```php
// In CellphoneRequest.php (if created)
public function rules()
{
    return [
        'sku' => ['required', 'regex:' . config('cellphone.imei_pattern')],
        'marca' => 'required|string|max:255',
        'modelo' => 'required|string|max:255',
        // ... custom rules
    ];
}
```

### Database Query Optimization

For large datasets, consider adding indexes:

```sql
-- Optimize searches by custom fields
ALTER TABLE products ADD INDEX idx_custom_field1 (product_custom_field1);
ALTER TABLE products ADD INDEX idx_custom_field2 (product_custom_field2);
ALTER TABLE products ADD INDEX idx_custom_field6 (product_custom_field6);
```

## 🔐 Security Configuration

### IMEI Privacy

To encrypt IMEI in database (advanced):

1. Create a mutator in Cellphone model
2. Use Laravel's encryption:

```php
public function setSkuAttribute($value)
{
    $this->attributes['sku'] = encrypt($value);
}

public function getSkuAttribute($value)
{
    return decrypt($value);
}
```

## ✅ Configuration Checklist

After installation, verify these configurations:

- [ ] Field mappings are correct
- [ ] Warranty options are configured
- [ ] IMEI validation pattern is set
- [ ] Permissions are assigned to roles
- [ ] Menu appears in correct location
- [ ] Language files are in place
- [ ] Dashboard widget is visible (if enabled)

---

For more information, see:
- [Installation Guide](installation.md)
- [Troubleshooting Guide](troubleshooting.md)
- [Main README](../README.md)
