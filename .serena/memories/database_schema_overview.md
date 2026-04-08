# Database Schema Overview

## Core Business Tables

### businesses
Main business/company records. Multi-tenancy support.
- `id` - Primary key
- `name` - Business name
- `currency_id` - Default currency
- `start_date` - Business start date
- `default_sales_tax` - Default tax rate
- `fy_start_month` - Fiscal year start month
- `accounting_method` - Cash/Accrual accounting
- `enable_product_expiry` - Enable expiry tracking
- `enabled_modules` - JSON array of enabled modules

### business_locations
Physical locations for each business. Multi-location support.
- `id` - Primary key
- `business_id` - Foreign key to businesses
- `name` - Location name
- `landmark` - Address details
- `city`, `state`, `country`, `zip_code` - Address fields
- `invoice_scheme_id`, `invoice_layout_id` - Invoice settings
- `is_active` - Active status (boolean)

## Product & Inventory Tables

### products
Main product catalog.
- `id` - Primary key
- `business_id` - Foreign key to businesses
- `name` - Product name
- `type` - single/variable/modifier/combo
- `brand_id` - Foreign key to brands
- `category_id`, `sub_category_id` - Categories
- `unit_id` - Unit of measurement
- `sku` - Stock keeping unit
- `barcode_type` - Barcode format
- `tax` - Tax rate ID
- `enable_stock` - Enable stock tracking (boolean)
- `is_inactive` - Product active status
- `not_for_selling` - Internal use only flag

### variations
Product variations (size, color, etc.).
- `id` - Primary key
- `product_id` - Foreign key to products
- `name` - Variation name (e.g., "Red-Large")
- `sub_sku` - Variation SKU
- `default_purchase_price` - Purchase cost
- `default_sell_price` - Selling price
- `deleted_at` - Soft delete

### product_variations
Variation templates (defines variation types).
- `id` - Primary key
- `product_id` - Foreign key to products
- `name` - Template name (e.g., "Color", "Size")

### variation_location_details
Location-specific inventory tracking (CRITICAL for multi-location).
- `id` - Primary key
- `variation_id` - Foreign key to variations
- `location_id` - Foreign key to business_locations
- `qty_available` - Current stock level
- `product_id` - Foreign key to products (denormalized)

### categories
Product categories (hierarchical).
- `id` - Primary key
- `business_id` - Foreign key to businesses
- `name` - Category name
- `short_code` - Category code
- `parent_id` - For subcategories

### brands
Product brands.
- `id` - Primary key
- `business_id` - Foreign key to businesses
- `name` - Brand name

### units
Units of measurement (kg, pcs, etc.).
- `id` - Primary key
- `business_id` - Foreign key to businesses
- `actual_name` - Full name
- `short_name` - Abbreviation
- `allow_decimal` - Allow decimal quantities

## Transaction Tables

### transactions
Main transaction table (sales, purchases, transfers, expenses).
- `id` - Primary key
- `business_id` - Foreign key to businesses
- `location_id` - Foreign key to business_locations
- `type` - sell/purchase/sell_return/purchase_return/opening_stock/transfer/expense
- `status` - draft/final/pending/received
- `payment_status` - paid/due/partial
- `contact_id` - Customer/supplier
- `transaction_date` - Transaction date
- `invoice_no` - Invoice number
- `ref_no` - Reference number
- `total_before_tax` - Subtotal
- `tax_amount` - Total tax
- `discount_amount` - Total discount
- `final_total` - Grand total
- `created_by` - User ID

### transaction_sell_lines
Individual line items for sales.
- `id` - Primary key
- `transaction_id` - Foreign key to transactions
- `product_id` - Foreign key to products
- `variation_id` - Foreign key to variations
- `quantity` - Quantity sold
- `unit_price` - Sale price per unit
- `line_discount_amount` - Line-level discount
- `item_tax` - Tax amount
- `tax_id` - Tax rate ID

### purchase_lines
Individual line items for purchases.
- `id` - Primary key
- `transaction_id` - Foreign key to transactions
- `product_id` - Foreign key to products
- `variation_id` - Foreign key to variations
- `quantity` - Quantity purchased
- `purchase_price` - Cost per unit
- `item_tax` - Tax amount

### transaction_payments
Payment records for transactions.
- `id` - Primary key
- `transaction_id` - Foreign key to transactions
- `business_id` - Foreign key to businesses
- `amount` - Payment amount
- `method` - cash/card/bank_transfer/cheque/custom
- `paid_on` - Payment date
- `payment_ref_no` - Reference number

## Contact Management Tables

### contacts
Customers and suppliers.
- `id` - Primary key
- `business_id` - Foreign key to businesses
- `type` - customer/supplier/both
- `name` - Contact name
- `supplier_business_name` - For suppliers
- `email` - Email address
- `mobile` - Phone number
- `city`, `state`, `country` - Address
- `credit_limit` - Maximum credit allowed
- `balance` - Current balance

### customer_groups
Customer categorization for pricing.
- `id` - Primary key
- `business_id` - Foreign key to businesses
- `name` - Group name

## Financial Tables

### accounts
Chart of accounts for accounting module.
- `id` - Primary key
- `business_id` - Foreign key to businesses
- `account_type_id` - Type of account
- `name` - Account name
- `account_number` - Account code

### account_transactions
Account-level transactions.
- `id` - Primary key
- `account_id` - Foreign key to accounts
- `type` - credit/debit
- `amount` - Transaction amount
- `operation_date` - Date of transaction

### cash_registers
POS cash register sessions.
- `id` - Primary key
- `business_id` - Foreign key to businesses
- `location_id` - Foreign key to business_locations
- `user_id` - Cashier user ID
- `status` - open/close
- `closed_at` - Session close time

## User & Permission Tables

### users
System users.
- `id` - Primary key
- `business_id` - Foreign key to businesses
- `first_name`, `last_name` - Name
- `username` - Login username
- `email` - Email address
- `password` - Hashed password
- `is_cmmsn_agnt` - Is sales commission agent

### roles
User roles (from Spatie Permission package).
- `id` - Primary key
- `name` - Role name
- `guard_name` - Auth guard
- `business_id` - Foreign key to businesses

### permissions
System permissions (from Spatie Permission package).
- `id` - Primary key
- `name` - Permission name (e.g., "product.view")
- `guard_name` - Auth guard

### model_has_roles
User-role assignments.

### role_has_permissions
Role-permission assignments.

## Configuration Tables

### tax_rates
Tax rate definitions.
- `id` - Primary key
- `business_id` - Foreign key to businesses
- `name` - Tax name
- `amount` - Tax percentage
- `is_tax_group` - Is group tax

### invoice_layouts
Invoice template definitions.
- `id` - Primary key
- `business_id` - Foreign key to businesses
- `name` - Layout name
- `design` - HTML/JSON template

### invoice_schemes
Invoice numbering schemes.
- `id` - Primary key
- `business_id` - Foreign key to businesses
- `scheme_type` - blank/year/custom
- `prefix` - Invoice prefix
- `start_number` - Starting number

### system
System-wide configuration and module versions.
- `key` - Setting key
- `value` - Setting value
- Used to track module installation status

## Common Query Patterns

### Get Location Inventory
```sql
SELECT 
    p.name,
    v.name as variation,
    vld.qty_available,
    bl.name as location
FROM variation_location_details vld
JOIN variations v ON vld.variation_id = v.id
JOIN products p ON vld.product_id = p.id
JOIN business_locations bl ON vld.location_id = bl.id
WHERE vld.location_id = ? AND p.business_id = ?
```

### Get Product with Stock
```sql
SELECT 
    p.*,
    SUM(vld.qty_available) as total_stock
FROM products p
LEFT JOIN variations v ON v.product_id = p.id
LEFT JOIN variation_location_details vld ON vld.variation_id = v.id
WHERE p.business_id = ?
GROUP BY p.id
```

### Business Context Pattern
Always filter by `business_id` in queries:
```php
$business_id = request()->session()->get('user.business_id');
$query->where('table.business_id', $business_id);
```