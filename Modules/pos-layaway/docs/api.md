# API Documentation

This document provides comprehensive information about the POS Layaway Module's API, models, and database structure.

## 📋 Table of Contents

- [Models and Relationships](#models-and-relationships)
- [Database Schema](#database-schema)
- [Controllers and Routes](#controllers-and-routes)
- [Events and Listeners](#events-and-listeners)
- [Helper Methods](#helper-methods)
- [Extension Points](#extension-points)

## 🏗️ Models and Relationships

### Layaway Model

**File:** `Modules/Layaway/Entities/Layaway.php`

#### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | bigint | Primary key |
| `business_id` | int | Business identifier |
| `contact_id` | int | Customer contact ID |
| `business_location_id` | int | Location where layaway was created |
| `created_by` | int | User who created the layaway |
| `layaway_number` | string | Unique layaway identifier |
| `total_amount` | decimal | Total layaway amount |
| `down_payment_percentage` | decimal | Down payment percentage |
| `down_payment_amount` | decimal | Required down payment amount |
| `balance_due` | decimal | Remaining balance |
| `payment_deadline` | datetime | Payment deadline |
| `status` | enum | Current status (pending, active, completed, cancelled) |
| `notes` | text | Additional notes |

#### Relationships

```php
// Customer who owns the layaway
public function contact()
{
    return $this->belongsTo(\App\Contact::class);
}

// Business location
public function location()
{
    return $this->belongsTo(\App\BusinessLocation::class, 'business_location_id');
}

// User who created the layaway
public function createdBy()
{
    return $this->belongsTo(\App\User::class, 'created_by');
}

// Items in the layaway
public function items()
{
    return $this->hasMany(LayawayItem::class);
}

// Payment history
public function payments()
{
    return $this->hasMany(LayawayPayment::class);
}

// Associated POS transaction
public function transaction()
{
    return $this->hasOne(\App\Transaction::class, 'layaway_id');
}
```

#### Scopes

```php
// Active layaways only
$activeLayaways = Layaway::active()->get();

// Overdue layaways
$overdueLayaways = Layaway::overdue()->get();

// Completed layaways
$completedLayaways = Layaway::completed()->get();
```

#### Key Methods

```php
// Generate unique layaway number
public static function generateLayawayNumber($business_id)

// Alternative atomic number generation
public static function generateLayawayNumberAtomic($business_id)

// Update balance after payment
public function updateBalance()

// Check if layaway is overdue
public function isOverdue()

// Get total paid amount
public function getTotalPaidAttribute()
```

### LayawayItem Model

**File:** `Modules/Layaway/Entities/LayawayItem.php`

#### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | bigint | Primary key |
| `layaway_id` | bigint | Reference to layaway |
| `product_id` | int | Product identifier |
| `variation_id` | int | Product variation |
| `quantity` | decimal | Item quantity |
| `unit_price` | decimal | Price per unit |
| `line_total` | decimal | Total line amount |

#### Relationships

```php
public function layaway()
{
    return $this->belongsTo(Layaway::class);
}

public function product()
{
    return $this->belongsTo(\App\Product::class);
}

public function variation()
{
    return $this->belongsTo(\App\Variation::class);
}
```

### LayawayPayment Model

**File:** `Modules/Layaway/Entities/LayawayPayment.php`

#### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | bigint | Primary key |
| `layaway_id` | bigint | Reference to layaway |
| `amount` | decimal | Payment amount |
| `payment_method` | string | Payment method used |
| `payment_date` | datetime | When payment was made |
| `processed_by` | int | User who processed payment |
| `cash_register_id` | int | Cash register used |
| `transaction_payment_id` | int | Link to transaction payment |
| `notes` | text | Payment notes |

#### Relationships

```php
public function layaway()
{
    return $this->belongsTo(Layaway::class);
}

public function processedBy()
{
    return $this->belongsTo(\App\User::class, 'processed_by');
}

public function cashRegister()
{
    return $this->belongsTo(\App\CashRegister::class);
}

public function transactionPayment()
{
    return $this->belongsTo(\App\TransactionPayment::class);
}
```

#### Accessors

```php
// Formatted payment method
public function getFormattedMethodAttribute()
{
    return ucfirst(str_replace('_', ' ', $this->payment_method));
}
```

## 🗄️ Database Schema

### Main Tables

#### `layaways`

```sql
CREATE TABLE `layaways` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `business_id` int unsigned NOT NULL,
    `contact_id` int unsigned NOT NULL,
    `business_location_id` int unsigned NOT NULL,
    `created_by` int unsigned NOT NULL,
    `layaway_number` varchar(50) NOT NULL,
    `total_amount` decimal(22,4) NOT NULL DEFAULT '0.0000',
    `down_payment_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
    `down_payment_amount` decimal(22,4) NOT NULL DEFAULT '0.0000',
    `balance_due` decimal(22,4) NOT NULL DEFAULT '0.0000',
    `payment_deadline` datetime NOT NULL,
    `status` enum('pending','active','completed','cancelled') NOT NULL DEFAULT 'pending',
    `notes` text,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `layaways_layaway_number_unique` (`layaway_number`),
    KEY `layaways_business_id_foreign` (`business_id`),
    KEY `layaways_contact_id_foreign` (`contact_id`),
    KEY `layaways_business_location_id_foreign` (`business_location_id`),
    KEY `layaways_created_by_foreign` (`created_by`)
);
```

#### `layaway_items`

```sql
CREATE TABLE `layaway_items` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `layaway_id` bigint unsigned NOT NULL,
    `product_id` int unsigned NOT NULL,
    `variation_id` int unsigned NULL,
    `quantity` decimal(22,4) NOT NULL DEFAULT '0.0000',
    `unit_price` decimal(22,4) NOT NULL DEFAULT '0.0000',
    `line_total` decimal(22,4) NOT NULL DEFAULT '0.0000',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `layaway_items_layaway_id_foreign` (`layaway_id`),
    KEY `layaway_items_product_id_foreign` (`product_id`)
);
```

#### `layaway_payments`

```sql
CREATE TABLE `layaway_payments` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `layaway_id` bigint unsigned NOT NULL,
    `amount` decimal(22,4) NOT NULL DEFAULT '0.0000',
    `payment_method` varchar(50) NOT NULL,
    `payment_date` datetime NOT NULL,
    `processed_by` int unsigned NOT NULL,
    `cash_register_id` int unsigned NULL,
    `transaction_payment_id` int unsigned NULL,
    `notes` text,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `layaway_payments_layaway_id_foreign` (`layaway_id`),
    KEY `layaway_payments_processed_by_foreign` (`processed_by`)
);
```

#### `sequences`

```sql
CREATE TABLE `sequences` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `key` varchar(255) NOT NULL,
    `value` int unsigned NOT NULL DEFAULT '0',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `sequences_key_unique` (`key`),
    KEY `sequences_key_index` (`key`)
);
```

### Modified System Tables

#### `transactions` (Modified)

Added column:
```sql
ALTER TABLE `transactions`
ADD COLUMN `layaway_id` bigint unsigned NULL AFTER `id`,
ADD FOREIGN KEY (`layaway_id`) REFERENCES `layaways`(`id`) ON DELETE SET NULL,
ADD INDEX `transactions_layaway_id_index` (`layaway_id`);
```

#### `transaction_payments` (Modified)

No direct modifications, but linked through `layaway_payments.transaction_payment_id`.

## 🛣️ Controllers and Routes

### LayawayController

**File:** `Modules/Layaway/Http/Controllers/LayawayController.php`

#### Routes

| Method | URI | Action | Permission |
|--------|-----|--------|------------|
| GET | `/layaway` | `index` | layaway.view |
| GET | `/layaway/create` | `create` | layaway.create |
| POST | `/layaway` | `store` | layaway.create |
| GET | `/layaway/{id}` | `show` | layaway.view |
| GET | `/layaway/{id}/edit` | `edit` | layaway.update |
| PUT | `/layaway/{id}` | `update` | layaway.update |
| DELETE | `/layaway/{id}` | `destroy` | layaway.delete |
| POST | `/layaway/{id}/cancel` | `cancel` | layaway.update |

#### Key Methods

```php
// List layaways with filtering
public function index(Request $request)

// Show layaway creation form
public function create()

// Store new layaway
public function store(Request $request)

// Display layaway details
public function show($id)

// Show edit form
public function edit($id)

// Update layaway
public function update(Request $request, $id)

// Cancel layaway
public function cancel($id)

// Delete layaway
public function destroy($id)
```

### LayawayPaymentController

**File:** `Modules/Layaway/Http/Controllers/LayawayPaymentController.php`

#### Routes

| Method | URI | Action | Permission |
|--------|-----|--------|------------|
| GET | `/layaway/payments` | `index` | layaway.view |
| GET | `/layaway/{id}/payments/create` | `create` | layaway.process_payment |
| POST | `/layaway/{id}/payments` | `store` | layaway.process_payment |
| GET | `/layaway/payments/{id}/receipt` | `printReceipt` | layaway.view |
| GET | `/layaway/{id}/payments/history` | `history` | layaway.view |

#### Key Methods

```php
// List all payments
public function index(Request $request)

// Show payment form
public function create($layaway_id)

// Process payment
public function store(Request $request, $layaway_id)

// Print payment receipt
public function printReceipt($id)

// Show payment history
public function history($layaway_id)
```

### DataController

**File:** `Modules/Layaway/Http/Controllers/DataController.php`

Handles module integration with Ultimate POS system:

```php
// Define user permissions
public function user_permissions()

// Add menu items
public function modifyAdminMenu()

// Parse notifications
public function parse_notification($notification)

// Dashboard widgets
public function dashboard_widgets($business_id)

// POS integration hooks
public function after_pos_create($pos_data)

// Form validation rules
public function pos_form_validation()
```

## 📡 Events and Listeners

### Model Events

The module uses Laravel model events for business logic:

```php
// Layaway model boot method
protected static function boot()
{
    parent::boot();

    // Auto-generate layaway number
    static::creating(function ($layaway) {
        if (empty($layaway->layaway_number)) {
            $layaway->layaway_number = self::generateLayawayNumber($layaway->business_id);
        }
    });
}
```

### Custom Events

You can listen for layaway events:

```php
// In a service provider
Layaway::created(function ($layaway) {
    // Send notification
    // Log activity
    // Update related systems
});

Layaway::updated(function ($layaway) {
    // Handle status changes
    // Update dependent records
});

LayawayPayment::created(function ($payment) {
    // Update layaway balance
    // Send receipt
    // Record transaction
});
```

## 🔧 Helper Methods

### Number Generation

```php
// Generate unique layaway number
$number = Layaway::generateLayawayNumber($business_id);
// Returns: LAY202509170001

// Atomic number generation (recommended for high-concurrency)
$number = Layaway::generateLayawayNumberAtomic($business_id);
```

### Status Management

```php
// Check layaway status
$layaway = Layaway::find(1);

if ($layaway->isOverdue()) {
    // Handle overdue layaway
}

// Update status
$layaway->status = 'completed';
$layaway->save();
```

### Balance Calculations

```php
// Get total paid
$totalPaid = $layaway->total_paid;

// Update balance after payment
$layaway->updateBalance();

// Check remaining balance
$remaining = $layaway->balance_due;
```

## 🔌 Extension Points

### Custom Validation

Extend validation rules by modifying request classes:

```php
// Create custom request class
class CustomLayawayRequest extends FormRequest
{
    public function rules()
    {
        return array_merge(parent::rules(), [
            'custom_field' => 'required|string|max:255',
            'special_validation' => 'required_if:type,special'
        ]);
    }
}
```

### Custom Business Logic

Add custom logic using model observers:

```php
// Create observer
class LayawayObserver
{
    public function creating(Layaway $layaway)
    {
        // Custom business logic before creating
    }

    public function created(Layaway $layaway)
    {
        // Custom business logic after creating
    }
}

// Register observer
Layaway::observe(LayawayObserver::class);
```

### Custom Notifications

Create custom notification classes:

```php
class CustomLayawayNotification extends Notification
{
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Custom Layaway Notification')
            ->line('Your layaway requires attention.')
            ->action('View Layaway', url('/layaway/' . $this->layaway->id));
    }
}
```

### API Extensions

Add custom API endpoints:

```php
// In routes/api.php
Route::prefix('layaway')->group(function () {
    Route::get('/stats', 'LayawayStatsController@index');
    Route::post('/bulk-update', 'LayawayBulkController@update');
});
```

### Custom Reports

Create custom reporting methods:

```php
class LayawayReportsController extends Controller
{
    public function salesReport(Request $request)
    {
        $layaways = Layaway::with(['payments', 'items'])
            ->whereBetween('created_at', [$request->start_date, $request->end_date])
            ->get();

        return view('layaway::reports.sales', compact('layaways'));
    }
}
```

## 🔗 Integration Examples

### POS Integration

```php
// After creating a POS sale
public function after_pos_create($pos_data)
{
    $layaway_id = request()->get('layaway_id');

    if ($layaway_id) {
        $layaway = Layaway::find($layaway_id);
        $layaway->status = 'completed';
        $layaway->save();
    }

    return $pos_data;
}
```

### Custom Dashboard Widget

```php
public function dashboard_widgets($business_id)
{
    return [
        'layaway_summary' => [
            'label' => 'Layaway Summary',
            'view' => 'layaway::dashboard.summary',
            'data' => [
                'total_active' => Layaway::active()->count(),
                'total_overdue' => Layaway::overdue()->count(),
                'pending_amount' => Layaway::active()->sum('balance_due')
            ]
        ]
    ];
}
```

---

This API documentation provides the foundation for extending and customizing the POS Layaway Module to meet your specific business requirements.