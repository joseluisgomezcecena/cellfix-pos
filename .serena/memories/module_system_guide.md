# Module System Integration Guide

## Overview
The application uses **nwidart/laravel-modules** for modular architecture. Modules are self-contained features that extend the core POS functionality.

## Module Structure

### Required Directory Structure
```
Modules/ModuleName/           # IMPORTANT: Must be StudlyCase (e.g., InventoryMultiLocation)
├── Config/
│   └── config.php           # Module configuration
├── Console/
│   ├── InstallCommand.php   # Optional install command
│   └── UninstallCommand.php # Optional uninstall command
├── Database/
│   ├── Migrations/          # Database migrations
│   ├── Seeders/             # Database seeders
│   └── factories/           # Model factories
├── Entities/                # Eloquent models
│   └── ModelName.php
├── Http/
│   ├── Controllers/         # Module controllers
│   ├── Middleware/          # Module middleware
│   └── Requests/            # Form requests
├── Providers/
│   ├── ModuleNameServiceProvider.php  # Main service provider
│   └── RouteServiceProvider.php       # Route registration
├── Resources/
│   ├── views/               # Blade templates
│   ├── assets/              # CSS/JS assets
│   └── lang/                # Translation files
├── Routes/
│   ├── web.php              # Web routes
│   └── api.php              # API routes
├── Tests/                   # Module tests
├── module.json              # Module metadata (REQUIRED)
├── composer.json            # Module dependencies
└── README.md                # Documentation
```

## Module Configuration Files

### 1. module.json (Required)
Defines module metadata and service providers.

```json
{
    "name": "ModuleName",
    "alias": "modulename",
    "description": "Module description",
    "keywords": ["keyword1", "keyword2"],
    "version": "1.0.0",
    "author": "Author Name",
    "email": "email@example.com",
    "homepage": "https://github.com/repo",
    "license": "MIT",
    "priority": 0,
    "providers": [
        "Modules\\ModuleName\\Providers\\ModuleNameServiceProvider"
    ],
    "aliases": {},
    "files": [],
    "requires": {
        "laravel": "^9.0|^10.0|^11.0",
        "php": "^8.0"
    }
}
```

**Important Notes:**
- `name` must match the folder name exactly (StudlyCase)
- `alias` is the lowercase version for routes/views
- `providers` array must list all service providers

### 2. Service Provider Pattern

```php
namespace Modules\ModuleName\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleNameServiceProvider extends ServiceProvider
{
    protected $moduleName = 'ModuleName';
    protected $moduleNameLower = 'modulename';

    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), 
            $this->moduleNameLower
        );
    }

    protected function registerViews()
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths(), [$sourcePath]), 
            $this->moduleNameLower
        );
    }

    protected function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(
                module_path($this->moduleName, 'Resources/lang'), 
                $this->moduleNameLower
            );
        }
    }

    protected function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
```

## Routes Integration

### Web Routes (Routes/web.php)
```php
Route::group([
    'middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'], 
    'prefix' => 'module-prefix'
], function () {
    Route::get('/dashboard', 'ControllerName@dashboard')->name('modulename.dashboard');
    Route::get('/index', 'ControllerName@index')->name('modulename.index');
    // More routes...
});
```

**Key Middleware:**
- `web` - Web routes middleware group
- `auth` - Requires authentication
- `SetSessionData` - Sets business session data
- `language` - Handles localization
- `timezone` - Handles timezone
- `AdminSidebarMenu` - Registers sidebar menu items

## Database Integration

### Core Tables Available
The module can access all core POS tables:
- `businesses` - Business records
- `business_locations` - Location records
- `products` - Product catalog
- `variations` - Product variations
- `variation_location_details` - Location-specific inventory
- `transactions` - Sales, purchases, transfers
- `transaction_payments` - Payment records
- `contacts` - Customers and suppliers
- `users` - System users
- And 200+ more tables

### Migration Pattern
```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModuleTable extends Migration
{
    public function up()
    {
        Schema::create('module_table', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            // More columns...
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('module_table');
    }
}
```

## Models Integration

### Accessing Core Models
```php
use App\Business;
use App\BusinessLocation;
use App\Product;
use App\User;
use App\Variation;
use App\VariationLocationDetails;

// In your module model
class ModuleModel extends Model
{
    public function business()
    {
        return $this->belongsTo('App\Business', 'business_id');
    }
    
    public function location()
    {
        return $this->belongsTo('App\BusinessLocation', 'location_id');
    }
}
```

## Permission Integration

### Adding Module Permissions
Use migrations to add permissions to the system:

```php
use Illuminate\Support\Facades\DB;

DB::table('permissions')->insert([
    [
        'name' => 'modulename.view',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'modulename.create',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ],
]);
```

### Checking Permissions in Controllers
```php
if (!auth()->user()->can('modulename.view')) {
    abort(403, 'Unauthorized action.');
}
```

## Menu Integration

Modules can add menu items via the `AdminSidebarMenu` middleware by creating a `DataController`:

```php
namespace Modules\ModuleName\Http\Controllers;

class DataController extends Controller
{
    public function superadmin_package()
    {
        return [
            [
                'name' => 'modulename',
                'label' => __('modulename::lang.module_name'),
                'default' => false,
            ],
        ];
    }
}
```

## Module Activation

### Module Status File
Module activation is tracked in `/modules_statuses.json`:
```json
{
    "ModuleName": true,
    "AnotherModule": false
}
```

### Module Detection
The system checks if a module is installed via `ModuleUtil`:
```php
$moduleUtil = new ModuleUtil();
if ($moduleUtil->isModuleInstalled('ModuleName')) {
    // Module is active
}
```

This checks:
1. Module exists in `Modules/` folder
2. Module is enabled in `modules_statuses.json`
3. Module version is registered in `system` table

## Common Issues & Solutions

### Issue: Module Not Detected
**Cause**: Folder name doesn't match `module.json` name
**Solution**: Rename folder to exact StudlyCase name from `module.json`

### Issue: Routes Not Working
**Cause**: Cache not cleared after adding routes
**Solution**: `php artisan config:clear && php artisan route:cache`

### Issue: Views Not Found
**Cause**: View namespace not registered
**Solution**: Ensure `registerViews()` is called in ServiceProvider boot()

### Issue: Migrations Not Running
**Cause**: Migration path not loaded
**Solution**: Use `php artisan module:migrate ModuleName` or specify path