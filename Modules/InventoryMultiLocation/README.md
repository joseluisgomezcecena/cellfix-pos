# Inventory Multi-Location Module

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/celfix/inventory-multi-location)
[![Laravel](https://img.shields.io/badge/laravel-9%7C10%7C11-orange.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/php-8.0%2B-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A comprehensive multi-location inventory management module for Laravel-based POS systems. This module provides advanced inventory tracking, stock transfers, location-based reporting, and comprehensive dashboard analytics.

## Features

### 📊 Dashboard Analytics
- **Total Locations** tracking with real-time statistics
- **Low Stock Alerts** with customizable thresholds
- **Pending Transfers** monitoring and management
- **Total Stock Value** calculation across all locations
- Interactive location overview with color-coded status indicators

### 🏢 Multi-Location Management
- Support for unlimited business locations
- Location-specific inventory tracking
- Permission-based location access control
- Real-time stock synchronization

### 📦 Inventory Management
- **Real-time stock tracking** across multiple locations
- **Advanced filtering** by category, brand, and stock status
- **Bulk operations** for efficient inventory management
- **Export functionality** for reporting and analysis
- **Search and filter** capabilities for quick product lookup

### 🔄 Stock Transfers
- **Inter-location transfers** with approval workflow
- **Bulk transfer** capabilities for multiple items
- **Transfer history** and audit trail
- **Status tracking** (pending, completed, cancelled)
- **Automated notifications** for transfer updates

### 📈 Reporting & Analytics
- **Location-based reports** with detailed insights
- **Stock movement tracking** and analysis
- **Low stock alerts** with automated notifications
- **Transfer reports** with comprehensive details
- **Export capabilities** for external analysis

### 🔐 Security & Permissions
- **Role-based access control** with granular permissions
- **Location-specific permissions** for enhanced security
- **Audit trails** for all inventory operations
- **User activity tracking** and logging

## Installation

### Requirements

- **PHP**: 8.0 or higher
- **Laravel**: 9.x, 10.x, or 11.x
- **MySQL**: 5.7 or higher / **PostgreSQL**: 10.0 or higher
- **Extensions**: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML

### Method 1: Automated Installation (Recommended)

1. **Download the module**:
   ```bash
   # Clone directly into your Modules directory
   cd /path/to/your/pos/Modules
   git clone https://github.com/celfix/inventory-multi-location.git InventoryMultiLocation
   cd InventoryMultiLocation
   ```

2. **Run the installer**:
   ```bash
   # Execute the automated installer
   php install.php
   ```

3. **Verify installation**:
   ```bash
   # Check if module is activated
   php artisan module:list
   ```

### Method 2: Manual Installation

1. **Download and extract**:
   - Download the latest release from [GitHub Releases](https://github.com/celfix/inventory-multi-location/releases)
   - Extract to your `Modules/InventoryMultiLocation` directory

2. **Install dependencies**:
   ```bash
   cd Modules/InventoryMultiLocation
   composer install --no-dev --optimize-autoloader
   ```

3. **Run migrations**:
   ```bash
   php artisan migrate
   ```

4. **Publish assets**:
   ```bash
   php artisan vendor:publish --provider="Modules\InventoryMultiLocation\Providers\InventoryMultiLocationServiceProvider"
   ```

5. **Enable the module**:
   ```bash
   php artisan module:enable InventoryMultiLocation
   ```

### Method 3: Composer Installation

```bash
# Add to your composer.json repositories
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/celfix/inventory-multi-location"
        }
    ]
}

# Install via composer
composer require celfix/inventory-multi-location
```

## Configuration

### Environment Setup

Add the following to your `.env` file:

```env
# Inventory Multi-Location Settings
INVENTORY_MULTI_DEFAULT_LOCATION=1
INVENTORY_MULTI_ENABLE_TRANSFERS=true
INVENTORY_MULTI_ENABLE_NOTIFICATIONS=true
INVENTORY_MULTI_LOW_STOCK_THRESHOLD=5
```

### Module Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="inventorymultilocation-config"
```

Edit `config/inventorymultilocation.php`:

```php
return [
    'default_location' => env('INVENTORY_MULTI_DEFAULT_LOCATION', 1),
    'enable_transfers' => env('INVENTORY_MULTI_ENABLE_TRANSFERS', true),
    'enable_notifications' => env('INVENTORY_MULTI_ENABLE_NOTIFICATIONS', true),
    'low_stock_threshold' => env('INVENTORY_MULTI_LOW_STOCK_THRESHOLD', 5),
];
```

## Usage

### Dashboard Access

Navigate to your POS system and access:
- **Main Dashboard**: `/inventory-multi/dashboard`
- **Inventory View**: `/inventory-multi/inventory`
- **Stock Transfers**: `/inventory-multi/transfers`
- **Reports**: `/inventory-multi/reports`

### Permissions

Assign the following permissions to users/roles:

- `inventory_multi.view` - View inventory across locations
- `inventory_multi.transfer` - Create and manage stock transfers
- `inventory_multi.manage` - Full inventory management
- `inventory_multi.bulk_actions` - Perform bulk operations

### API Endpoints

The module provides RESTful API endpoints:

```
GET    /api/inventory-multi/locations           # List all locations
GET    /api/inventory-multi/inventory/{id}      # Get location inventory
POST   /api/inventory-multi/transfers          # Create transfer
PUT    /api/inventory-multi/transfers/{id}     # Update transfer
DELETE /api/inventory-multi/transfers/{id}     # Cancel transfer
```

## Screenshots

### Dashboard Overview
![Dashboard](docs/images/dashboard.png)

### Inventory Management
![Inventory](docs/images/inventory.png)

### Stock Transfers
![Transfers](docs/images/transfers.png)

## Customization

### Adding Custom Fields

Extend the inventory tracking by adding custom fields:

```php
// In a service provider
Schema::table('variation_location_details', function (Blueprint $table) {
    $table->string('custom_field')->nullable();
});
```

### Custom Reports

Create custom reports by extending the ReportController:

```php
namespace Modules\InventoryMultiLocation\Http\Controllers;

class CustomReportController extends ReportController
{
    public function customReport()
    {
        // Your custom report logic
    }
}
```

## Troubleshooting

### Common Issues

1. **Module not appearing in menu**:
   ```bash
   php artisan cache:clear
   php artisan config:cache
   ```

2. **Permissions not working**:
   ```bash
   php artisan permission:cache-reset
   ```

3. **Database issues**:
   ```bash
   php artisan migrate:rollback --path=Modules/InventoryMultiLocation/Database/Migrations
   php artisan migrate --path=Modules/InventoryMultiLocation/Database/Migrations
   ```

### Debug Mode

Enable debug mode in your `.env`:

```env
INVENTORY_MULTI_DEBUG=true
```

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Development Setup

1. Fork the repository
2. Clone your fork
3. Install dependencies: `composer install`
4. Run tests: `php artisan test Modules/InventoryMultiLocation/Tests`
5. Create a feature branch
6. Make your changes
7. Submit a pull request

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

## Support

- **Documentation**: [Wiki](https://github.com/celfix/inventory-multi-location/wiki)
- **Issues**: [GitHub Issues](https://github.com/celfix/inventory-multi-location/issues)
- **Email**: admin@daseo.co

## License

This module is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

- **CELFIX Team** - Primary development
- **Laravel Community** - Framework and inspiration
- **Contributors** - See [CONTRIBUTORS.md](CONTRIBUTORS.md)

---

**Made with ❤️ for the Laravel POS community**