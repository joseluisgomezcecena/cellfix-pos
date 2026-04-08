# POS Layaway Module

A comprehensive layaway management module for Ultimate POS systems. This module allows businesses to manage layaway transactions, process payments, and track customer commitments with full integration into the existing POS workflow.

## 🚀 Features

- **Complete Layaway Management**: Create, edit, and manage layaway transactions
- **Payment Processing**: Process partial and full payments with receipt generation
- **Status Tracking**: Track layaway status (pending, active, completed, cancelled)
- **Transaction Integration**: Full integration with Ultimate POS transaction system
- **Automated Numbering**: Race-condition safe layaway number generation
- **Customer Management**: Link layaways to existing customer accounts
- **Reporting**: Built-in payment history and transaction tracking
- **Multi-Location Support**: Works with multiple business locations

## 📋 Requirements

- **Ultimate POS**: Version 6.0 or higher
- **PHP**: 8.0 or higher
- **Laravel**: 9.x or 10.x
- **MySQL**: 5.7 or higher / MariaDB 10.3 or higher
- **Laravel Modules**: nwidart/laravel-modules ^9.0

## ⚡ Quick Installation

```bash
# 1. Download the module
wget https://github.com/username/pos-layaway/archive/main.zip
unzip main.zip
cd pos-layaway-main

# 2. Run automated installer
php install.php

# 3. Verify installation
php scripts/verify-installation.php
```

## 📖 Manual Installation

For detailed installation instructions, see [Installation Guide](docs/installation.md).

## 🔧 Configuration

After installation, configure the module according to your business needs. See [Configuration Guide](docs/configuration.md).

## 🛠️ Features Overview

### Layaway Creation
- Create layaways with multiple products
- Set down payment percentages
- Define payment deadlines
- Add customer notes and special instructions

### Payment Processing
- Process partial payments
- Handle full payment completion
- Generate payment receipts
- Track payment history

### Status Management
- **Pending**: Initial layaway state
- **Active**: Layaway with at least one payment
- **Completed**: Fully paid layaway
- **Cancelled**: Cancelled layaway with stock restoration

### Integration Features
- Seamless POS system integration
- Transaction recording for reporting
- Stock management integration
- Customer account integration

## 📊 Database Schema

The module creates the following tables:
- `layaways` - Main layaway records
- `layaway_items` - Products in each layaway
- `layaway_payments` - Payment history
- `sequences` - Number generation support

System table modifications:
- `transactions` - Adds layaway_id foreign key
- `transaction_payments` - Links to layaway payments

## 🧪 Testing

Run the included tests to verify module functionality:

```bash
# Feature tests
php artisan test tests/Feature/LayawayNumberGenerationTest.php

# Full test suite
php scripts/verify-installation.php
```

## 📚 Documentation

- [Installation Guide](docs/installation.md)
- [Configuration Guide](docs/configuration.md)
- [API Documentation](docs/api.md)
- [Troubleshooting](docs/troubleshooting.md)

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

---

**Made with ❤️ for Ultimate POS community**