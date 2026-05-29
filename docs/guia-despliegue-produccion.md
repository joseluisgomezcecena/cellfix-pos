# Guía de despliegue a producción — Celfix POS (sin acceso a artisan)

**Versión:** feature/pos-improved
**Fecha:** 21 de mayo, 2026

Esta guía explica cómo subir esta versión al servidor (pos.celfix.mx) **sin necesidad de comandos
artisan**. Solo necesitas: acceso FTP/administrador de archivos y phpMyAdmin.

> ⚠️ **Producción NO tiene ninguno de los cambios de 2026 todavía.** Este es un despliegue completo
> de todas las funciones nuevas (técnicos, metas/comisiones, cortes diarios, terminales de tarjeta,
> reparaciones, tipo de cambio, descuentos por sucursal).

---

## Resumen de lo que vas a hacer

1. **Respaldar** la base de datos y los archivos actuales de producción.
2. **Subir los archivos** nuevos y modificados.
3. **Aplicar los cambios de base de datos** corriendo un solo archivo SQL en phpMyAdmin.
4. **Limpiar las cachés** borrando unos archivos (esto reemplaza a `php artisan ... :clear`).
5. **Verificar** que todo funcione.

---

## PASO 1 — Respaldo (OBLIGATORIO antes de tocar nada)

1. **Base de datos:** en phpMyAdmin, selecciona la base de producción → pestaña **Exportar** →
   método rápido → formato SQL → Continuar. Guarda ese archivo.
2. **Archivos:** descarga (o copia en el servidor) una copia de la carpeta del proyecto, o al menos
   de las carpetas `app/`, `resources/`, `routes/`, `public/js/` y `lang/`.

Si algo sale mal, con estos respaldos puedes volver atrás.

---

## PASO 2 — Subir archivos

Sube por FTP **respetando las rutas** (mismo lugar que en tu proyecto). No hay que "compilar" nada:
no existe `package.json` ni build de assets; los archivos `.js` se suben tal cual.

### Archivos MODIFICADOS (reemplazar los del servidor)

```
app/Console/Kernel.php
app/Http/Controllers/ProductController.php
app/Http/Controllers/SellController.php
app/Http/Controllers/SellPosController.php
app/Http/Middleware/AdminSidebarMenu.php
app/Utils/TransactionUtil.php
lang/en/lang_v1.php
lang/es/lang_v1.php
public/js/lang/es.js
public/js/pos.js
routes/web.php
resources/views/product/create.blade.php
resources/views/product/edit.blade.php
resources/views/sale_pos/create.blade.php
resources/views/sale_pos/edit.blade.php
resources/views/sale_pos/partials/edit_discount_modal.blade.php
resources/views/sale_pos/partials/payment_row_form.blade.php
resources/views/sale_pos/partials/payment_type_details.blade.php
resources/views/sale_pos/partials/pos_form_actions.blade.php
resources/views/sale_pos/product_row.blade.php
resources/views/sale_pos/show.blade.php
resources/views/transaction_payment/single_payment_view.blade.php
```

### Archivos NUEVOS (subir)

```
app/CardTerminal.php
app/DailyCut.php
app/Technician.php
app/VendorCommissionTarget.php
app/StockCorrection.php
app/SalesGoal.php
app/Console/Commands/DailyCutCommand.php
app/Utils/DailyCutUtil.php
app/Http/Controllers/CardTerminalController.php
app/Http/Controllers/CommissionTargetController.php
app/Http/Controllers/DailyCutController.php
app/Http/Controllers/ExchangeRateController.php
app/Http/Controllers/TechnicianController.php
app/Http/Controllers/VendorReportController.php
app/Http/Controllers/StockCorrectionController.php
app/Http/Controllers/SalesDashboardController.php
app/Exports/DailyCutsExport.php
app/Exports/TechniciansReportExport.php
app/Exports/WeeklyCutByLocationExport.php
app/Exports/WeeklyVendorReportExport.php
app/Exports/SalesDashboardExport.php
public/js/pos_payment_methods.js
resources/views/card_terminal/        (carpeta completa)
resources/views/commission_target/    (carpeta completa)
resources/views/daily_cut/            (carpeta completa)
resources/views/exchange_rate/        (carpeta completa)
resources/views/technician/           (carpeta completa)
resources/views/vendor_report/        (carpeta completa)
resources/views/stock_correction/     (carpeta completa)
resources/views/sales_dashboard/       (carpeta completa)
resources/views/sale_pos/partials/card_simple_modal.blade.php
resources/views/sale_pos/partials/cash_payment_modal.blade.php
resources/views/sale_pos/partials/cheque_payment_modal.blade.php
resources/views/sale_pos/partials/transfer_payment_modal.blade.php
database/migrations/2026_*.php         (todas las de 2026 — ver nota abajo)
```

### NO subir (dejar como están en producción)

```
.env                       (configuración del servidor, NO tocar)
.claude/                   (configuración local de desarrollo)
conv.md, _audit_*.php, _audit_*.log, docs/*.xlsx   (archivos de análisis, no son del sistema)
vendor/                    (no cambió: no se agregaron paquetes de composer)
```

> **Nota sobre las migraciones:** subir los archivos de `database/migrations/2026_*.php` es opcional
> y **inofensivo** (como nunca corres `artisan migrate`, solo quedan archivados). El cambio real de
> base de datos lo hace el SQL del Paso 3. Conviene subirlos para mantener el código completo.

### Cambios adicionales — columna "Apartado" y reporte de equipos apartados (sin cambios de BD)

Modificados:
```
app/Http/Controllers/ReportController.php                     (columna APARTADO en detalle de stock)
resources/views/product/partials/product_stock_details.blade.php
app/Http/Controllers/ProductController.php                    (columna STATUS en lista de productos)
resources/views/product/index.blade.php
resources/views/product/partials/product_list.blade.php
Modules/Layaway/Http/Controllers/LayawayController.php        (reporte de equipos apartados)
```
Nuevos:
```
Modules/Layaway/Resources/views/reserved_equipos.blade.php
```
(Las rutas y el menú ya van en `routes/web.php` y `AdminSidebarMenu.php`, y los textos en `lang/es|en/lang_v1.php`.)

### Comisión por producto de reparación (nueva tabla `repair_product_commissions`)

> **SQL:** está incluido en `deploy_produccion.sql` (9 tablas). Bloque individual:
> ```sql
> CREATE TABLE IF NOT EXISTS `repair_product_commissions` (
>   `id` bigint unsigned NOT NULL AUTO_INCREMENT,
>   `business_id` int unsigned NOT NULL,
>   `product_id` int unsigned NOT NULL,
>   `commission_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
>   `created_at` timestamp NULL DEFAULT NULL,
>   `updated_at` timestamp NULL DEFAULT NULL,
>   PRIMARY KEY (`id`),
>   UNIQUE KEY `unique_repair_commission` (`business_id`,`product_id`),
>   KEY `repair_product_commissions_product_id_index` (`product_id`)
> ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
> ```

Modificados:
```
app/Http/Controllers/TechnicianController.php   (modal + comisión por producto en el reporte)
app/Exports/TechniciansReportExport.php
resources/views/technician/index.blade.php
resources/views/technician/report.blade.php
```
Nuevos:
```
app/RepairProductCommission.php
resources/views/technician/repair_commissions_modal.blade.php
database/migrations/2026_05_26_000000_create_repair_product_commissions_table.php
```

### Orden de reparación: recepción → entrega (columna `transactions.repair_status`)

> **SQL:** incluido en `deploy_produccion.sql`. Bloque individual (columna, idempotente):
> ```sql
> SET @e := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'transactions' AND column_name = 'repair_status');
> SET @s := IF(@e = 0, 'ALTER TABLE `transactions` ADD COLUMN `repair_status` varchar(20) NULL', 'DO 1');
> PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
> ```

Modificados:
```
app/Http/Controllers/SellPosController.php        (hook recepción: marca repair_status + anticipo)
resources/views/sale_pos/partials/pos_form_actions.blade.php   (botones Recibir/Entregar + modal recepción)
public/js/pos.js                                  (handlers de recepción)
```
Nuevos:
```
app/Http/Controllers/RepairOrderController.php    (búsqueda de pendientes + entrega)
resources/views/sale_pos/partials/repair_delivery_modal.blade.php
database/migrations/2026_05_27_000000_add_repair_status_to_transactions_table.php
```

---

## PASO 3 — Aplicar los cambios de base de datos

En lugar de `php artisan migrate`, vas a correr **un solo archivo SQL** ya preparado y **probado**
contra una copia exacta del esquema de producción (corrió sin errores).

1. Abre **phpMyAdmin** y selecciona la base de **producción**.
2. Ve a la pestaña **SQL** (o **Importar** y elige el archivo).
3. Pega/importa el contenido de:

   > `docs/deploy_produccion.sql`

4. Ejecuta. Crea 8 tablas nuevas y agrega 7 columnas, más una limpieza de marcas inválidas.

Qué hace ese SQL:

- **Crea tablas:** `card_terminals`, `daily_cuts`, `discount_locations`, `technicians`,
  `technician_locations`, `vendor_commission_targets`, `stock_corrections`, `sales_goals`.
- **Agrega columnas:** `business.cash_exchange_rate`, `products.reward_points`,
  `transaction_payments.card_terminal_id`, `transaction_payments.denomination_breakdown`,
  `transaction_sell_lines.technician_id`, `transaction_sell_lines.repair_entry_date`,
  `transaction_sell_lines.repair_anticipo`.
- **Limpia** marcas que se importaron como fórmulas de Excel (nombres que empiezan con `=`).

Usa `CREATE TABLE IF NOT EXISTS`, así que si lo corres dos veces no truena en las tablas (las
columnas sí darían error de "columna duplicada" si lo corres dos veces — solo córrelo una vez).

### (Opcional) Registrar las migraciones como aplicadas

Si quieres que la tabla `migrations` quede al día (por si algún día se usa artisan), corre también
este SQL **después** del anterior. Es opcional; el sistema funciona sin esto.

```sql
INSERT INTO migrations (migration, batch) VALUES
('2026_04_08_203516_clean_excel_formula_brands_from_brands_table', 100),
('2026_04_08_230013_create_discount_locations_table', 100),
('2026_04_25_011958_add_denominations_to_transaction_payments_table', 100),
('2026_04_27_170541_add_reward_points_to_products_table', 100),
('2026_04_30_005145_create_card_terminals_table', 100),
('2026_04_30_005521_add_card_terminal_id_to_transaction_payments_table', 100),
('2026_04_30_193947_create_daily_cuts_table', 100),
('2026_05_01_102812_add_cash_exchange_rate_to_business_table', 100),
('2026_05_12_163243_create_technicians_table', 100),
('2026_05_12_163446_create_technician_locations_table', 100),
('2026_05_12_165939_add_technician_id_to_transaction_sell_lines_table', 100),
('2026_05_13_093625_add_commission_per_repair_to_technicians_table', 100),
('2026_05_13_120902_add_repair_fields_to_transaction_sell_lines_table', 100),
('2026_05_14_022025_restructure_vendor_commission_targets_per_user', 100),
('2026_05_21_230000_create_stock_corrections_table', 100),
('2026_05_22_000000_create_sales_goals_table', 100);
```

---

## PASO 4 — Limpiar cachés (reemplaza a los comandos artisan)

Como no puedes correr `php artisan view:clear / route:clear / config:clear`, lo logras
**borrando archivos** desde el FTP/administrador de archivos del servidor:

1. Borra **todo el contenido** de la carpeta:
   ```
   storage/framework/views/        (borra los .php de adentro; NO borres la carpeta)
   ```
   Esto fuerza a recompilar las vistas Blade (equivale a `view:clear`).

2. Si existen, **borra** estos archivos (equivale a `route:clear` y `config:clear`):
   ```
   bootstrap/cache/config.php
   bootstrap/cache/routes.php
   bootstrap/cache/routes-v7.php
   ```
   Si no existen, no pasa nada — significa que producción no cachea esos.

Laravel los regenera solos en la siguiente visita a la página.

> Si después de subir todo ves una página en blanco o error 500, casi siempre es por caché vieja:
> repite este Paso 4.

---

## PASO 5 — Verificación

1. Entra a `https://pos.celfix.mx` e inicia sesión.
2. En el **menú** deberían aparecer las nuevas secciones (Técnicos, Metas y comisiones, Reporte
   semanal de vendedores, Cortes diarios, Terminales de tarjeta, según el rol).
3. Abre el **POS** (`/pos/create`) con **Ctrl + F5** (recarga forzada para tomar el JS nuevo) y
   verifica que cargue bien y que los modales de pago aparezcan.
4. Haz una **venta de prueba** pequeña y confirma que se guarda.
5. Entra a un **producto** y revisa que abra sin error (por las columnas nuevas).

Si algo truena:
- Página en blanco / 500 → repite **Paso 4** (cachés).
- "Unknown column ..." → faltó correr el **SQL del Paso 3** (o se subió código sin aplicar la BD).
- JS viejo / el POS no cambia → recarga con **Ctrl + F5** o limpia caché del navegador.

---

## Rollback (si necesitas volver atrás)

1. Restaura el **respaldo de la base de datos** del Paso 1 (Importar en phpMyAdmin).
2. Restaura los **archivos** del respaldo del Paso 1.
3. Repite el **Paso 4** (limpiar cachés).

---

## Checklist rápido

- [ ] Respaldo de BD hecho
- [ ] Respaldo de archivos hecho
- [ ] Archivos modificados subidos
- [ ] Archivos nuevos subidos (incluye carpetas de vistas completas)
- [ ] `deploy_produccion.sql` ejecutado en phpMyAdmin (1 sola vez)
- [ ] (Opcional) registros en tabla `migrations`
- [ ] `storage/framework/views/` vaciada
- [ ] `bootstrap/cache/config.php` y `routes*.php` borrados (si existían)
- [ ] Login OK, menú OK, POS OK (Ctrl+F5), venta de prueba OK
