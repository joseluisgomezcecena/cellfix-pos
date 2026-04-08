# Configuration Guide

This guide covers how to configure the POS Layaway Module after installation.

## 🔐 User Permissions

The module includes several permission levels that can be assigned to user roles:

### Available Permissions

| Permission | Description |
|------------|-------------|
| `layaway.create` | Create new layaway transactions |
| `layaway.view` | View layaway records and details |
| `layaway.update` | Edit existing layaway records |
| `layaway.delete` | Delete layaway records |
| `layaway.process_payment` | Process payments on layaways |

### Assigning Permissions

1. Navigate to **Settings** → **User Management** → **Roles**
2. Select the role you want to modify
3. In the permissions section, check the appropriate layaway permissions
4. Save the changes

### Recommended Permission Sets

**Manager/Admin Role:**
- ✅ All layaway permissions

**Sales Staff:**
- ✅ `layaway.create`
- ✅ `layaway.view`
- ✅ `layaway.process_payment`

**Cashier:**
- ✅ `layaway.view`
- ✅ `layaway.process_payment`

## ⚙️ Module Settings

The module can be configured through various settings:

### Default Settings

Default configuration is set in the module's service provider. You can modify these by editing:
`Modules/Layaway/Config/config.php`

```php
return [
    'default_down_payment_percentage' => 20,
    'max_payment_deadline_days' => 90,
    'min_down_payment_percentage' => 10,
    'enable_overdue_notifications' => true,
    'layaway_number_prefix' => 'LAY',
    'auto_cancel_overdue_days' => 30
];
```

### Business Rules Configuration

#### Down Payment Requirements

Set minimum down payment requirements:

```php
// Minimum 20% down payment
'min_down_payment_percentage' => 20,

// Default down payment when creating layaways
'default_down_payment_percentage' => 25,
```

#### Payment Deadlines

Configure payment deadline constraints:

```php
// Maximum 90 days for payment deadline
'max_payment_deadline_days' => 90,

// Default deadline when creating layaways
'default_payment_deadline_days' => 30,
```

#### Number Generation

Customize layaway number format:

```php
// Prefix for layaway numbers
'layaway_number_prefix' => 'LAY',

// Date format in numbers (Ymd = YYYYMMDD)
'layaway_number_date_format' => 'Ymd',

// Sequence padding (0001, 0002, etc.)
'layaway_number_sequence_padding' => 4,
```

## 🎨 User Interface Customization

### Menu Configuration

The module adds menu items automatically. To customize:

Edit `Modules/Layaway/Http/Controllers/DataController.php`:

```php
public function modifyAdminMenu()
{
    return Menu::modify('admin-sidebar-menu', function ($menu) {
        $menu->dropdown(
            __('layaway::lang.layaway'),
            function ($sub) {
                $sub->url(
                    action('\Modules\Layaway\Http\Controllers\LayawayController@create'),
                    __('layaway::lang.new_layaway')
                );
                $sub->url(
                    action('\Modules\Layaway\Http\Controllers\LayawayController@index'),
                    __('layaway::lang.all_layaways')
                );
                // Add more menu items here
            },
            ['icon' => 'fa fa-shopping-bag']
        )->order(32);
    });
}
```

### Custom Translations

Add translations for different languages in:
`Modules/Layaway/Resources/lang/[language]/lang.php`

Example for Spanish (`es/lang.php`):

```php
return [
    'layaway' => 'Apartado',
    'new_layaway' => 'Nuevo Apartado',
    'all_layaways' => 'Todos los Apartados',
    'payment_collection' => 'Cobro de Pagos',
    // ... more translations
];
```

## 🗄️ Database Configuration

### Sequence Management

The module uses a sequences table for generating unique numbers. Configure in:
`config/database.php`

No special configuration needed - uses your existing database connection.

### Index Optimization

For high-volume installations, consider adding these indexes:

```sql
-- Optimize layaway lookups
ALTER TABLE layaways ADD INDEX idx_business_status (business_id, status);
ALTER TABLE layaways ADD INDEX idx_payment_deadline (payment_deadline);

-- Optimize payment queries
ALTER TABLE layaway_payments ADD INDEX idx_payment_date (payment_date);
ALTER TABLE layaway_payments ADD INDEX idx_layaway_date (layaway_id, payment_date);
```

## 📧 Notification Configuration

### Email Notifications

Configure email notifications in your `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

### Notification Types

The module can send notifications for:
- Payment due reminders
- Overdue layaways
- Payment confirmations
- Layaway completion

Enable/disable in module config:

```php
'notifications' => [
    'payment_due_days_before' => 3,
    'send_payment_confirmations' => true,
    'send_completion_notifications' => true,
    'send_overdue_warnings' => true,
]
```

## 🏪 Multi-Location Setup

### Location-Specific Settings

For multi-location businesses, configure per-location settings:

```php
'location_settings' => [
    'separate_numbering_per_location' => false,
    'location_prefix_in_numbers' => false,
    'independent_payment_deadlines' => false,
]
```

### Location Permissions

Restrict users to specific locations by configuring location permissions in Ultimate POS settings.

## 🔄 Integration Settings

### POS Integration

Configure how layaways integrate with your POS workflow:

```php
'pos_integration' => [
    'auto_create_transaction' => true,
    'include_in_daily_reports' => true,
    'separate_layaway_register' => false,
]
```

### Payment Methods

Configure available payment methods for layaways:

```php
'allowed_payment_methods' => [
    'cash',
    'card',
    'check',
    'bank_transfer',
    // Add custom payment methods
]
```

## 📊 Reporting Configuration

### Report Settings

Configure reporting options:

```php
'reporting' => [
    'include_cancelled_in_reports' => false,
    'group_payments_by_date' => true,
    'show_commission_on_layaways' => true,
]
```

### Dashboard Widgets

Enable/disable dashboard widgets:

```php
'dashboard_widgets' => [
    'active_layaways_count' => true,
    'overdue_layaways_alert' => true,
    'pending_payments_summary' => true,
    'today_collections' => true,
]
```

## 🛡️ Security Configuration

### Access Control

Configure security settings:

```php
'security' => [
    'require_manager_approval_for_cancellation' => false,
    'log_all_layaway_actions' => true,
    'encrypt_customer_notes' => false,
]
```

### Audit Trail

Enable detailed logging:

```php
'audit' => [
    'log_status_changes' => true,
    'log_payment_processing' => true,
    'log_modifications' => true,
    'retention_days' => 365,
]
```

## 🔧 Advanced Configuration

### Custom Validation Rules

Add custom validation in form requests:

```php
// In LayawayRequest.php
public function rules()
{
    return [
        'total_amount' => 'required|numeric|min:10|max:10000',
        'down_payment_percentage' => 'required|numeric|min:10|max:50',
        // Add custom rules
    ];
}
```

### Custom Business Logic

Extend the module with custom business logic:

```php
// In a service provider or custom class
Layaway::creating(function ($layaway) {
    // Custom logic before creating layaway
});

Layaway::created(function ($layaway) {
    // Custom logic after creating layaway
});
```

### API Customization

Configure API endpoints and responses:

```php
'api' => [
    'enable_api_endpoints' => true,
    'require_api_authentication' => true,
    'rate_limit_per_minute' => 60,
    'include_customer_data' => true,
]
```

## 🎯 Performance Optimization

### Caching

Enable caching for better performance:

```php
'cache' => [
    'enable_layaway_caching' => true,
    'cache_duration_minutes' => 60,
    'cache_reports' => true,
]
```

### Database Optimization

For large datasets:

```php
'database' => [
    'enable_query_optimization' => true,
    'paginate_large_lists' => true,
    'items_per_page' => 50,
]
```

## 🔄 Backup and Maintenance

### Automated Backups

Include layaway data in backups:

```bash
# Add to your backup script
php artisan backup:run --only-db
```

### Data Retention

Configure data retention policies:

```php
'retention' => [
    'keep_completed_layaways_days' => 1095, // 3 years
    'keep_cancelled_layaways_days' => 365,  // 1 year
    'auto_cleanup_enabled' => false,
]
```

---

**Next Steps:**
- Review the [API Documentation](api.md)
- Check the [Troubleshooting Guide](troubleshooting.md)
- Customize the module for your business needs