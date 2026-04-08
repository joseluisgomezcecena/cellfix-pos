# HideContactCustomFields Module

Version: 1.0.0

## Description

This module hides contact custom fields 6-10 from all contact forms in the Celfix POS system, including:
- POS "Add New Customer" modal
- Contact create page
- Contact edit page

## Installation

1. **Enable the module:**
   ```bash
   php artisan module:enable HideContactCustomFields
   ```

2. **Run the installation command:**
   ```bash
   php artisan hide-contact-fields:install
   ```

3. **Clear caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

## What It Does

- Backs up original `create.blade.php` and `edit.blade.php` files to `storage/app/backups/contact_views/`
- Publishes modified view files that exclude custom fields 6-10
- Registers module version in the system table

## Uninstallation

To restore the original contact forms with all 10 custom fields:

```bash
php artisan hide-contact-fields:uninstall
```

Then clear caches:
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Files Modified

- `resources/views/contact/create.blade.php` - Contact creation form
- `resources/views/contact/edit.blade.php` - Contact edit form

## Backup Location

Original files are backed up to:
`storage/app/backups/contact_views/`

## Module Structure

```
Modules/HideContactCustomFields/
├── Config/
│   └── config.php
├── Console/
│   ├── InstallCommand.php
│   └── UninstallCommand.php
├── Providers/
│   ├── HideContactCustomFieldsServiceProvider.php
│   └── RouteServiceProvider.php
├── Resources/
│   └── views/
│       └── contact/
│           ├── create.blade.php (modified - fields 6-10 removed)
│           └── edit.blade.php (modified - fields 6-10 removed)
├── Routes/
│   ├── web.php
│   └── api.php
├── module.json
├── composer.json
└── README.md
```

## Support

For issues or questions, contact Celfix support.

## License

MIT License
