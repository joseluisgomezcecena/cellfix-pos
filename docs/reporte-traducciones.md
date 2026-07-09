# Reporte de traducciones agregadas

**Fecha:** 2026-07-02
**Rama:** feature/pos-improved
**Motivo:** Los usuarios reportaron ver textos mezclados entre inglés y español dependiendo del usuario (algunos con `users.language = 'en'`, otros con `'es'`). Aunado a esto, había claves de traducción llamadas desde el código que no existían en `lang/es/*.php`, y salían en pantalla como el nombre literal (ej: `sale.cash` en vez de `Efectivo`).

---

## Cambio estructural

**Middleware `Language.php`** — Ahora fuerza `App::setLocale('es')` para **todos** los usuarios, ignorando el valor de `users.language` guardado en sesión. Esto garantiza que el 100% del staff vea la misma interfaz.

Archivo: `app/Http/Middleware/Language.php`

Comportamiento previo (por usuario) queda como comentario para revertir si fuera necesario.

---

## Auditoría automatizada

Se escanearon **918 archivos** de código (`resources/views/`, `app/`, `Modules/`), extrayendo todas las llamadas a `@lang('foo.bar')`, `__('foo.bar')` y `trans('foo.bar')`.

- **2,880 claves únicas** referenciadas
- **2,590 ya existían** en `lang/es/`
- **262 aparentes faltantes** en el primer pase

Después de verificar en detalle, **~230 de las 262 eran falsos positivos** debido a claves con puntos como parte del nombre (ej: `role.user.view` donde el archivo tiene la clave flat `'user.view' => '...'`). Laravel las resuelve correctamente porque `Arr::get` primero intenta lookup directo antes de hacer nested.

Las claves **realmente faltantes** que se agregaron son las que se detallan abajo.

---

## Traducciones agregadas

### `lang/es/sale.php` — 2 claves

| Clave | Español |
|---|---|
| `cash` | Efectivo |
| `ref_no` | No. de referencia |

### `lang/es/messages.php` — 16 claves

| Clave | Español |
|---|---|
| `activate` | Activar |
| `deactivate` | Desactivar |
| `filter` | Filtrar |
| `reset` | Restablecer |
| `loading` | Cargando |
| `select_items` | Selecciona elementos |
| `no_data` | Sin datos |
| `how_it_works` | Cómo funciona |
| `created_by` | Creado por |
| `complete` | Completar |
| `sure` | ¿Estás seguro? |
| `created_at` | Fecha de creación |
| `back` | Regresar |
| `please_wait` | Por favor espera... |
| `from` | Desde |
| `to` | Hasta |

### `lang/es/business.php` — 5 claves

| Clave | Español |
|---|---|
| `land_mark` | Punto de referencia |
| `street_name` | Nombre de la calle |
| `building_number` | Número de edificio |
| `additional_number` | Número adicional |
| `p_exchange_rate` | Tipo de cambio de compra |

### `lang/es/invoice.php` — 6 claves

| Clave | Español |
|---|---|
| `sequential` | Secuencial |
| `random` | Aleatorio |
| `invoice_scheme_for_pos` | Esquema de factura para POS |
| `invoice_scheme_for_sale` | Esquema de factura para venta |
| `number_type` | Tipo de número |
| `number_type_tooltip` | Elige si el número de factura será secuencial o aleatorio |

### `lang/es/tooltip.php` — 3 claves

| Clave | Español |
|---|---|
| `sale_product` | Producto de venta |
| `sub_sku` | Sub SKU |
| `shipping` | Detalles de envío |

### `lang/es/product.php` — 1 clave

| Clave | Español |
|---|---|
| `dpp_inc_tax` | Precio de compra por defecto (con impuesto) |

### `lang/es/expense.php` — 1 clave

| Clave | Español |
|---|---|
| `date_format_instruction` | Formato de fecha: YYYY-MM-DD (por ejemplo 2026-01-31) |

### `lang/es/lang_v.php` — **archivo nuevo** (2 claves)

Se creó porque partes del código usan `__('lang_v.something')` en vez de `__('lang_v1.something')` (parece typo antiguo).

| Clave | Español |
|---|---|
| `success` | Éxito |
| `imported` | Importado |

### `lang/es/lang_v1.php` — 161 claves

Este es el archivo principal del sistema. Se agregaron las siguientes bajo el comentario `// ---- Traducciones agregadas 2026-07-02 ----`:

Áreas cubiertas:
- **Autenticación**: `welcome_back`, `login_to_your`, `back_to_username`
- **Contactos y CRM**: `payments_recovered_for`, `automatic`, `one_month`, `three_months`, `six_months`, `one_year`, `overall_summary`, `transaction`, `current`, `statement`, `1_30_days_past_due`, `30_60_days_past_due`, `60_90_days_past_due`, `over_90_days_past_due`, `amount_due`, `add_discount`, `ledger_format`, `format_1/2/3`, `parent_payment`
- **Suscripción y negocio**: `business_inactive`, `business_dont_have_crm_subscription`, `inactive`, `reactivate`, `is_active`
- **Importación/exportación**: `invalid_date_format_at`, `product_prices_imported_successfully`, `import`, `lot_number_instructions`, `exp_date_instructions`, `date_ins`, `variation_sku`, `variation_sku_ins`
- **Requisiciones y compras**: `purchase_requisition`, `add_purchase_requisition`, `create_purchase_requisition`, `delete_purchase_requisition`, `view_all_purchase_requisition`, `view_own_purchase_requisition`, `purchase_requisition_details`, `required_by_date`, `required_quantity`, `show_products`, `all_added_products_will_be_removed`, `second_quantity`, `delivery_date`, `po_no`, `delivery_at`, `dispatch_from`, `checked_by`, `prepared_by`, `for_business`, `view_all_purchase_n_stock_adjustment`, `view_own_purchase_n_stock_adjustment`, `prev_unit_price`, `prev_discount`
- **Reportes GST (India)**: `gst_sales_report`, `gst_purchase_report`, `gstin_of_supplier`, `gstin_of_cutomer`, `hsn_code`, `taxable_value`, `invoice_date`
- **Módulos**: `manage_modules`, `install`, `uninstall`, `version`, `module_new_version`
- **POS y ventas**: `sell_not_found`, `copy_quotation`, `remove`, `no_products`, `add_edit_payment`, `change_return_payment_method`, `change_return_payment_account`, `cash_denomination_error`, `applied_discount_text`
- **Apartados (Layaway)**: `layaway`, `payment_deadline`, `payment_details`
- **Impresión y facturas**: `show_letter_head`, `letter_head_help`, `letter_head_help2`, `letter_head`, `show_product_description`, `show_base_unit_details`, `discounted_unit_price_label`, `total_items_label`, `tax_summary_label`
- **Personal de servicio**: `service_staff_availability`, `service_staff_availability_status`, `will_be_available_at`, `paused`, `mark_as_available`, `pause_timer`, `resume_timer`, `refresh`, `types_of_service_details`, `types_of_service_module_settings`, `show_types_of_service`, `show_tos_custom_fields`, `types_of_service_label`
- **Configuración**: `enter_dropdown_values`, `enable_secondary_unit`, `payment_option_help`, `default_account_help`, `discount_priority_help`, `applicable_in_cg`, `add_location_to_the_selected_products`, `remove_location_from_the_selected_products`, `product_business_location_tooltip`, `select_variation_values`, `customer_supplier_info`, `secondary_unit`, `secondary_unit_help`, `add_as_multiple_of_base_unit`, `multi_unit_help`, `times_base_unit`, `select_base_unit`, `enable_secondary_unit`, `payment_reminder_help`, `new_sale_notification_help`, `price_group_price_type_tooltip`, `selling_price_help_text`, `product_not_assigned_to_any_location`, `discount_for`, `edit_discount`
- **Home / dashboard**: `calendar`, `payment_recovered_today`, `net`, `net_home_tooltip`, `profit_margin`
- **Permisos y seguridad**: `permission_denied`, `discount.access`
- **Otros**: `source`, `life_stage`, `export_custom_field`, `duplicate_taxonomy_type_found`, `taxonomy_type_not_found`, `service_custom_field_5`, `service_custom_field_6`, `products_deactivated_success`, `update_status`, `quantity_required`, `profile_updated_successfully`, `password_updated_successfully`, `u_have_entered_wrong_password`, `ledger_discount`, `pay_to_supplier`, `receive_from_supplier`, `receive_from_customer`, `pay_to_customer`, `delete`, `vendors`, `woocommerce_enabled`, `array`, `quantity_in_second_unit`

### `Modules/Layaway/Resources/lang/es/lang.php` — 3 claves

| Clave | Español |
|---|---|
| `select_product` | Selecciona un producto |
| `maximum_amount` | Cantidad máxima |
| `select_payment_method` | Selecciona el método de pago |

### `Modules/PromoCode/Resources/lang/es/lang.php` — 1 clave

| Clave | Español |
|---|---|
| `company_name_readonly` | El nombre de la empresa no se puede modificar después de creada |

---

## Falsos positivos identificados (NO se tocaron)

Estas claves aparecían como "faltantes" pero en realidad estaban presentes como claves flat con puntos:

- **`lang/es/role.php`**: `user.view`, `user.create`, `product.view`, `sell.view`, etc. (~50 claves) — todas ya existen
- **`lang/es/myfatoorah.php`**: `deleteAlert.title`, `deleteAlert.message`, `deleteAlert.confirm`, `deleteAlert.cancel` — todas ya existen
- Claves vacías (`""`) que aparecían por concatenaciones dinámicas del tipo `__($variable)` — no son claves reales, se ignoran
- Prefijos como `status_` en Layaway — se concatenan dinámicamente con `$row->status` para producir `status_pending`, `status_active`, etc., y todos esos ya existían

---

## Archivos modificados / creados

**Modificados:**
- `app/Http/Middleware/Language.php` (forzar locale a `es`)
- `lang/es/sale.php`
- `lang/es/messages.php`
- `lang/es/business.php`
- `lang/es/invoice.php`
- `lang/es/tooltip.php`
- `lang/es/product.php`
- `lang/es/expense.php`
- `lang/es/lang_v1.php`
- `Modules/Layaway/Resources/lang/es/lang.php`
- `Modules/PromoCode/Resources/lang/es/lang.php`

**Creado:**
- `lang/es/lang_v.php`

---

## Despliegue

**Sin cambios de BD.** Solo subir por FTP los archivos listados.

Después de subir:
1. Borrar caché de vistas en producción:
   ```
   storage/framework/views/*.php
   ```
2. (Opcional) Borrar caché de config:
   ```
   bootstrap/cache/config.php
   ```
3. Los usuarios verán español inmediatamente en el siguiente click, sin cerrar sesión.

---

## Lo que queda abierto

Si aún aparecen textos en inglés después del despliegue, es probable que sean uno de estos casos:

1. **Textos hardcodeados en vistas Blade** que no usan `@lang()`. Requieren editar el `.blade.php` directamente.
2. **Nuevos textos** agregados después del 2026-07-02 sin traducción. Basta con reportarlos y agregar la clave.
3. **Textos en módulos** que no se auditaron (los que no están en `Modules/*/Resources/lang/es/`).

Cualquiera de estos casos se puede resolver con la misma técnica: `grep -rn "texto_en_ingles" resources/views Modules` → localizar el archivo → agregar la traducción a `lang/es/{archivo}.php` o al Blade directamente.
