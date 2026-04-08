# POS Cellphone Module

A specialized cellphone/mobile device inventory management module for Ultimate POS systems. Perfect for cellphone repair companies, retailers, and accessory sellers.

## 🚀 Features

- **IMEI Management**: 15-digit IMEI validation and duplicate checking
- **Brand & Model Tracking**: Organize inventory by Marca (Brand) and Modelo (Model)
- **Warranty Integration**: Pre-configured 3, 6, and 12-month warranty options
- **Condition Tracking**: Estado field (Nuevo/Usado/Reacondicionado)
- **Physical Location**: Ubicación field for rack/shelf tracking
- **Advanced Search & Filters**: Filter by brand, model, IMEI, condition, warranty
- **Dashboard Widget**: Statistics showing inventory by brand and condition
- **Permissions System**: Granular permissions (create, view, update, delete, export)
- **Multi-Location Support**: Compatible with Ultimate POS multi-location features

## 📋 Requirements

- **Ultimate POS**: Version 6.0 or higher
- **PHP**: 8.0 or higher
- **Laravel**: 9.x or 10.x
- **MySQL**: 5.7 or higher / MariaDB 10.3 or higher
- **Laravel Modules**: nwidart/laravel-modules ^9.0

## ⚡ Quick Installation

```bash
# 1. Download the module
wget https://github.com/celfix/pos-cellphone/archive/main.zip
unzip main.zip
cd pos-cellphone-main

# 2. Copy to your Ultimate POS directory
cp -r pos-cellphone-main /path/to/ultimate-pos/

# 3. Navigate to Ultimate POS directory
cd /path/to/ultimate-pos

# 4. Run automated installer
php pos-cellphone-main/install.php

# 5. Verify installation
php pos-cellphone-main/scripts/verify-installation.php
```

## 📖 Manual Installation

If you prefer to install manually:

### Step 1: Copy Module Files

```bash
# From the pos-cellphone directory
cp -r src/Modules/Cellphone /path/to/ultimate-pos/Modules/

# Set permissions
chmod -R 755 /path/to/ultimate-pos/Modules/Cellphone
```

### Step 2: Run Module Seeder

```bash
# From Ultimate POS directory
cd /path/to/ultimate-pos
php artisan module:seed Cellphone
```

### Step 3: Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
composer dump-autoload
```

### Step 4: Assign Permissions

1. Go to **Settings** → **User Management** → **Roles**
2. Edit user roles to assign cellphone permissions:
   - `cellphone.create` - Create new cellphones
   - `cellphone.view` - View cellphones
   - `cellphone.update` - Edit cellphones
   - `cellphone.delete` - Delete cellphones
   - `cellphone.export` - Export reports

## 🛠️ Usage

### Accessing the Module

After installation, you'll see **"Equipos Celulares"** (Cellphone Equipment) in the sidebar menu with:
- **Nuevo Celular** - Add new cellphone
- **Todos los Celulares** - View all cellphones

### Adding a Cellphone

1. Click "Nuevo Celular"
2. Fill in required fields:
   - **IMEI**: 15-digit IMEI number (validated for format and duplicates)
   - **Marca**: Brand (e.g., Samsung, Apple, Xiaomi)
   - **Modelo**: Model (e.g., Galaxy S21, iPhone 13)
   - **Estado**: Condition (Nuevo/Usado/Reacondicionado)
   - **Garantía**: Select warranty period (3, 6, or 12 months)
   - **Ubicación**: Physical location/rack
   - **Precio**: Selling price
3. Click Save

### Searching & Filtering

Use the advanced filter options to search by:
- Marca (Brand)
- Modelo (Model)
- IMEI
- Estado (Condition)
- Garantía (Warranty)

## 🔧 Configuration

### Field Mapping

The module uses existing product custom fields. Edit `/Modules/Cellphone/Config/config.php` to customize:

```php
'field_mapping' => [
    'marca' => 'product_custom_field1',      // Brand
    'modelo' => 'product_custom_field2',     // Model
    'ubicacion' => 'product_custom_field3',  // Physical location
    'estado' => 'product_custom_field4',     // Condition
    'observaciones' => 'product_custom_field5', // Notes
    'cellphone_flag' => 'product_custom_field6', // Module identifier
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

## 📚 Documentation

- [Installation Guide](docs/installation.md) - Detailed installation instructions
- [Configuration Guide](docs/configuration.md) - Module configuration options
- [Troubleshooting](docs/troubleshooting.md) - Common issues and solutions
- [API Documentation](docs/api.md) - Module features and usage

## ✨ Technical Implementation

- **No Database Migrations Required**: Uses existing product table structure
- **Extends Product Model**: Seamless integration with Ultimate POS
- **Custom Field Mapping**: Maps cellphone fields to product_custom_field1-6
- **IMEI Storage**: Uses SKU field with validation
- **Warranty Integration**: Leverages existing warranty system
- **Module Isolation**: Uses cellphone_flag to separate from regular products

## 🔄 Version History

See [CHANGELOG.md](CHANGELOG.md) for version history and updates.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Open an issue on GitHub
- Check the [Troubleshooting Guide](docs/troubleshooting.md)
- Review the documentation

## ⚠️ Important Notes

- **Backup your database** before installation
- Test in a development environment first
- Ensure Ultimate POS compatibility
- Follow the installation guide carefully

## 🙏 Credits

Developed by the Celfix Team for the Ultimate POS community.

---

**Made with ❤️ for cellphone retailers and repair shops**
