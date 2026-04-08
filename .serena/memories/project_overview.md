# Celfix POS - Project Overview

## Purpose
Celfix POS is an enterprise-grade Point of Sale (POS) system built on Laravel. It's based on Ultimate POS by Ultimate Fosters and designed for managing multi-business, multi-location retail operations with comprehensive inventory, sales, purchase, and financial management capabilities.

## Project Identity
- **Name**: Celfix POS
- **Environment**: Development (dev.celfix.mx)
- **Base Path**: `/www/wwwroot/dev.celfix.mx`
- **Database**: MySQL (`sql_dev_celfix_m`)
- **Domain**: https://dev.celfix.mx

## Core Capabilities
1. **Multi-Business & Multi-Location Support**
   - Handle multiple businesses within one installation
   - Each business can have multiple locations
   - Location-specific inventory tracking and permissions

2. **Product & Inventory Management**
   - Product variations and templates
   - Multi-location inventory tracking
   - Stock transfers between locations
   - Stock adjustments and opening stock
   - Barcode generation and product racks
   - Expiry date and lot/serial number tracking
   - Warranties and product media

3. **Transaction Management**
   - POS sales and invoicing
   - Purchase orders and purchases
   - Sales and purchase returns
   - Quotations and sales orders
   - Expense tracking
   - Payment processing with multiple methods

4. **Contact Management**
   - Customers and suppliers
   - Customer groups with special pricing
   - Credit limit and balance tracking
   - Contact payments and statements

5. **Financial Management**
   - Accounts and account types
   - Transaction payments tracking
   - Cash register management
   - Tax management (single and group taxes)
   - Ledger reports and accounting

6. **User & Permission System**
   - Role-based access control (RBAC) using Spatie Laravel Permission
   - User contact access restrictions
   - Location-based permissions
   - Sales commission agent tracking

7. **Reporting & Analytics**
   - Sales, purchase, stock, and payment reports
   - Activity logs and audit trails
   - Custom dashboard configurations
   - Export functionality (Excel, PDF)

## Modular Architecture
The system uses **nwidart/laravel-modules** for extensibility, allowing features to be added as independent modules in the `Modules/` directory. Currently 22 modules are registered including Accounting, CRM, Ecommerce, Manufacturing, Repair, HMS (Hospital Management), Gym, and custom modules.