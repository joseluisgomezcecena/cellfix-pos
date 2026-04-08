# Tech Stack & Architecture

## Core Framework
- **Laravel**: 9.52.16
- **PHP**: 8.0+
- **Database**: MySQL 5.7+ / PostgreSQL 10.0+
- **Server**: Linux (5.15.0-151-generic)

## Key Laravel Packages

### Authentication & Authorization
- `laravel/passport` (11.6.1) - OAuth2 server for API authentication
- `laravel/ui` (4.x) - Auth scaffolding
- `spatie/laravel-permission` (^5.5) - Role and permission management

### Module System
- `nwidart/laravel-modules` (^9.0) - Modular application architecture
- `nwidart/laravel-menus` (6.0.x-dev) - Dynamic menu system

### Database & Data Management
- `maatwebsite/excel` (^3.1.8) - Excel import/export
- `yajra/laravel-datatables-oracle` (^9.19) - DataTables server-side processing
- `laravel/legacy-factories` (^1.3) - Factory support

### File Storage & Backup
- `league/flysystem-aws-s3-v3` (^3.0) - AWS S3 integration
- `spatie/flysystem-dropbox` (^2.0) - Dropbox integration  
- `spatie/laravel-backup` (^8.0) - Database and file backups

### Activity Tracking
- `spatie/laravel-activitylog` (^4.4) - Activity logging and auditing

### PDF & Barcode Generation
- `barryvdh/laravel-dompdf` (^2.0) - PDF generation
- `mpdf/mpdf` (^8.1) - Advanced PDF generation
- `milon/barcode` (^9.0) - Barcode generation

### Form & HTML Helpers
- `laravelcollective/html` (^6.3) - Form and HTML helpers

### Payment Gateways
- `stripe/stripe-php` (^7.122) - Stripe payment processing
- `srmklive/paypal` (^3.0) - PayPal integration
- `razorpay/razorpay` (2.*) - Razorpay integration
- `unicodeveloper/laravel-paystack` (^1.0) - Paystack integration
- `myfatoorah/laravel-package` (^2.2) - MyFatoorah integration
- `knox/pesapal` (1.5) - Pesapal integration

### Third-party Integrations
- `automattic/woocommerce` (^3.0) - WooCommerce integration
- `openai-php/laravel` (^0.4.1) - OpenAI API integration
- `pusher/pusher-php-server` (^5.0) - Real-time notifications
- `aloha/twilio` (^4.0) - SMS notifications

### Utilities & Tools
- `consoletvs/charts` (^6.5) - Chart generation
- `giggsey/libphonenumber-for-php` (^8.12) - Phone number validation
- `guzzlehttp/guzzle` (^7.2) - HTTP client

### Development Tools
- `barryvdh/laravel-debugbar` (^3.6) - Debug toolbar
- `knuckleswtf/scribe` (^4.14) - API documentation
- `arcanedev/log-viewer` (9.x) - Log viewer

## Architecture Patterns

### MVC Architecture
- **Models**: Located in `app/` directory (e.g., Product.php, Business.php)
- **Controllers**: Located in `app/Http/Controllers/`
- **Views**: Located in `resources/views/`

### Modular Architecture
- Modules stored in `Modules/` directory
- Each module is self-contained with its own MVC structure
- Module activation managed via `modules_statuses.json`
- Service Providers auto-register module components

### Database Architecture
- Eloquent ORM for database operations
- Migrations for schema management
- Seeders for data population
- Factories for testing data

### Frontend Stack
- Blade templating engine
- DataTables for table rendering
- Charts.js for analytics
- Tailwind CSS utility classes (tw-* prefixed)
- SVG icons for UI elements