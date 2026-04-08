# Code Style & Conventions

## PHP Code Style

### PSR Standards
The project follows **PSR-2** coding standard as configured in `.php_cs`:
- PSR-2 compliant formatting
- Short array syntax `[]` instead of `array()`
- Alphabetically ordered imports
- No unused imports
- Strict parameter typing enabled

### PHP CS Fixer Configuration
```php
'@PSR2' => true,
'strict_param' => true,
'array_syntax' => ['syntax' => 'short'],
'ordered_imports' => ['sortAlgorithm' => 'alpha'],
'no_unused_imports' => true,
```

## Naming Conventions

### Classes
- **PascalCase** for class names: `ProductController`, `InventoryTransfer`
- **Singular** names for models: `Product`, `Business`, `Transaction`
- **Plural** names for tables: `products`, `businesses`, `transactions`

### Methods & Variables
- **camelCase** for methods: `getLocationData()`, `isModuleInstalled()`
- **snake_case** for database columns: `business_id`, `created_at`, `from_location_id`
- **camelCase** for local variables in PHP

### Constants & Configuration
- **SCREAMING_SNAKE_CASE** for constants: `APP_NAME`, `DB_CONNECTION`
- Configuration keys use snake_case

## File Organization

### Controllers
- Located in `app/Http/Controllers/`
- Named with `Controller` suffix
- Use dependency injection in constructors
- Protected/private properties for utilities
- DocBlocks for class and method documentation

**Example Pattern:**
```php
class ProductController extends Controller
{
    protected $productUtil;
    protected $moduleUtil;
    
    public function __construct(ProductUtil $productUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Permission check
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }
        
        // Business context
        $business_id = request()->session()->get('user.business_id');
        
        // Implementation
    }
}
```

### Models
- Located in `app/` directory
- Extend `Illuminate\Database\Eloquent\Model`
- Use `$guarded` or `$fillable` for mass assignment protection
- Define relationships using Eloquent methods
- Use `$casts` for attribute casting
- Scope methods prefixed with `scope`: `scopeActive()`, `scopeForLocation()`

**Example Pattern:**
```php
class Product extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function brand()
    {
        return $this->belongsTo(Brands::class, 'brand_id');
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_inactive', 0);
    }
}
```

### Modules
- Located in `Modules/` directory
- **StudlyCase** folder names: `InventoryMultiLocation`, `AssetManagement`
- Each module follows the structure defined in `config/modules.php`

## Permission Patterns
- Permission format: `{resource}.{action}` (e.g., `product.view`, `product.create`)
- Always check permissions at the beginning of controller methods
- Use `auth()->user()->can()` for permission checking
- Return 403 Unauthorized for permission failures

## Session & Context
- Business ID stored in session: `request()->session()->get('user.business_id')`
- User permissions: `auth()->user()->can('permission.name')`
- Location permissions: `auth()->user()->permitted_locations()`

## Database Queries
- Use query builder or Eloquent ORM
- Eager load relationships to avoid N+1 queries
- Use `with()` for eager loading
- Filter by business_id in all queries
- Respect location permissions when querying data

## Comments & Documentation
- DocBlocks for classes and public methods
- Return type and parameter documentation
- Inline comments for complex logic only
- No commented-out code in production