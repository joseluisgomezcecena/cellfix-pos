# HideCardPayment Module

## Description
This module removes the card payment option from the POS system without modifying core files. It survives software updates and can be easily enabled/disabled.

## How It Works
The module uses Laravel View Composers to intercept POS views before they are rendered and filters out the 'card' payment type from the `payment_types` variable.

## Features
- ✅ Removes "Tarjeta" (Card) express payment button from POS
- ✅ Removes "Tarjeta" option from payment method dropdowns
- ✅ Works across all POS screens (create, edit, payment modal)
- ✅ No core file modifications - survives software updates
- ✅ Existing card payment data remains intact
- ✅ Can be easily disabled to restore card payments

## Installation

### Module is already installed and enabled!

The module is already active in your system. If you need to disable it:

1. Edit `/modules_statuses.json`
2. Change `"HideCardPayment": true` to `"HideCardPayment": false`
3. Clear caches:
```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

## Configuration

### Config File
Location: `Modules/HideCardPayment/Config/config.php`

```php
return [
    'enabled' => true,  // Set to false to disable the module

    'hidden_payment_types' => [
        'card',  // Add more payment types here to hide them
    ],
];
```

### Hiding Additional Payment Types
To hide other payment types (e.g., cheque, bank_transfer), edit the config file:

```php
'hidden_payment_types' => [
    'card',
    'cheque',
    'bank_transfer',
],
```

Then clear caches:
```bash
php artisan config:clear
```

## Files Structure
```
Modules/HideCardPayment/
├── Config/
│   └── config.php                      # Module configuration
├── Http/
│   └── ViewComposers/
│       └── PaymentTypesComposer.php    # Filters payment types
├── Providers/
│   ├── HideCardPaymentServiceProvider.php  # Main service provider
│   └── RouteServiceProvider.php        # Route provider (empty)
├── module.json                         # Module metadata
└── README.md                           # This file
```

## How to Disable

### Method 1: Module Status (Recommended)
1. Edit `/modules_statuses.json`
2. Set `"HideCardPayment": false`
3. Clear caches

### Method 2: Config File
1. Edit `Modules/HideCardPayment/Config/config.php`
2. Set `'enabled' => false`
3. Clear caches

## Troubleshooting

### Card payment still showing
1. Make sure the module is enabled in `modules_statuses.json`
2. Clear all caches:
```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
php artisan route:clear
```
3. Refresh your browser (Ctrl+F5)

### Module not loading
1. Check that folder name is exactly: `HideCardPayment` (case-sensitive)
2. Check that `module.json` exists
3. Verify module is enabled in `modules_statuses.json`
4. Run: `composer dump-autoload`

## Technical Details

### View Composer Targets
The module applies to these views:
- `sale_pos.create`
- `sale_pos.edit`
- `sale_pos.partials.pos_form_actions`
- `sale_pos.partials.payment_modal`
- `sale_pos.partials.payment_row_form`
- `sale_pos.partials.payment_row`
- `sell.create`
- `sell.edit`
- `transaction_payment.*`

### Core Payment Types
Available payment types in the system:
- `cash` - Cash
- `card` - Card (Hidden by this module)
- `cheque` - Cheque
- `bank_transfer` - Bank Transfer
- `other` - Other
- `custom_pay_1` to `custom_pay_7` - Custom payment methods

## Version
- **Version:** 1.0.0
- **Laravel:** ^9.0|^10.0|^11.0
- **PHP:** ^8.0

## Support
For issues or questions, check:
1. Module configuration in `Config/config.php`
2. Module status in `/modules_statuses.json`
3. Laravel logs in `storage/logs/`

## Author
Celfix

## License
MIT
