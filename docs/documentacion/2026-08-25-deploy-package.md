# Deploy package — 2026-08-25

Documento autosuficiente con TODOS los cambios pendientes de subir a producción (`pos.celfix.mx`). Diseñado para pegarse en una sesión nueva de Claude sin contexto previo.

**Estado al 2026-08-25:**
- ✅ SQL: `ALTER daily_cut_vendor_counts` (Bloque B, columnas manuales) — YA APLICADO EN PROD
- ⏳ Todo lo demás — PENDIENTE

**Deploy process:** prod es hosting cPanel sin CLI ni artisan. Deploy = subir archivos por FTP + correr SQL a mano en phpMyAdmin. Sin migraciones automáticas.

**Orden obligatorio:** SQL primero (crea/altera columnas), archivos después (el código nuevo espera esas columnas).

---

## Índice de bloques

- [Bloque A — Fix reparaciones en limbo](#bloque-a) — 1 archivo, 0 SQL
- [Bloque B — Rediseño weekly + denominaciones](#bloque-b) — 5 archivos, 1 SQL (ya aplicado)
- [Bloque C — Módulo REPARACIÓN DE TIENDA](#bloque-c) — 10 archivos, 4 SQL

---

## Bloque A — Fix reparaciones en limbo al editar venta

### Por qué

`TransactionUtil::editSellLine()` sobreescribía con `null` los campos:
- `transaction_sell_lines.technician_id`
- `transaction_sell_lines.repair_entry_date`
- `transaction_sell_lines.repair_anticipo`

Esto ocurría cuando el form del POS normal (`/sells/edit`) editaba una venta que era reparación. Como `updateSellTransaction()` no tocaba `transactions.repair_status`, la transaction seguía en `repair_status='pending'` pero las líneas quedaban sin técnico — la reparación seguía apareciendo en `/repair-orders/pending` pero "en limbo": sin técnico visible, sin aparecer en reportes de técnicos, sin poder cerrarse.

### Fix

Cambio de `!empty` a `array_key_exists`: si el key no viene en el input, conserva el valor previo del sell_line.

### Deploy

**FTP:**
- `app/Utils/TransactionUtil.php`

**SQL:** ninguno.

### Verificación

1. Ve una reparación existente en `/sells`, ábrela con "Editar"
2. Guarda sin cambios
3. En BD: `technician_id`, `repair_entry_date`, `repair_anticipo` **siguen con los valores previos**

Query opcional para auditar reparaciones ya rotas antes del fix:
```sql
SELECT t.id, t.invoice_no, DATE_FORMAT(t.transaction_date,'%Y-%m-%d') AS fecha,
       bl.name AS sucursal, c.name AS cliente, c.mobile, t.final_total
FROM transactions t
LEFT JOIN business_locations bl ON bl.id = t.location_id
LEFT JOIN contacts c ON c.id = t.contact_id
WHERE t.business_id = 2 AND t.type = 'sell' AND t.repair_status = 'pending'
  AND NOT EXISTS (
    SELECT 1 FROM transaction_sell_lines tsl
    WHERE tsl.transaction_id = t.id AND tsl.technician_id IS NOT NULL
  )
ORDER BY t.transaction_date DESC;
```

Las que salgan necesitan que el gerente les re-asigne técnico manualmente desde `/repair-orders/admin`.

---

## Bloque B — Rediseño reporte semanal + denominaciones

### Por qué

El reporte semanal mostraba "Diferencia -\$16,240" comparando efectivo neto de ventas contra el contado del cajero, sin restar los gastos que salieron del mismo cajón durante el día. El gerente veía diferencias grandes y creía que era faltante puro, cuando la mayor parte eran gastos legítimos ya pagados.

La nueva estructura pedida por el user:

```
Categorías…
TOTAL             $X
EFECTIVO          $X (MXN + USD, SIN restar cambio — BRUTO)
TARJETA           $X
  ↳ BANBAJIO      $X   ← orden pedido: BANBAJIO, BANORTE, BANAMEX
  ↳ BANORTE       $X
  ↳ BANAMEX       $X
TRANSFERENCIAS    $X
CHEQUES           $X
SUBTOTAL DINERO   $X  ← suma sistema
DINERO POR VENDEDOR $X ← todo lo capturado manualmente por el cajero
GASTOS            $X
CAMBIO ENTREGADO  $X
TOTAL DINERO      $X ← Subtotal − Cambio − Gastos
DIFERENCIA        $X ← Vendedor − Total (verde = sobra, rojo = falta, gris = sin captura)
```

Denominaciones ahora:
- TOTAL EFECTIVO es bruto puro (sin restar cambio)
- Columnas CAMBIO EFECTIVO y GASTOS movidas a la derecha
- Fila del cajero: **inputs manuales** para BanBajio/Banorte/Banamex/Transfer/Cheque (para "empatar" cuando el sistema y su conteo no coinciden)
- Tipo de cambio: input arriba, resultado (USD × rate) abajo

### Deploy

**SQL** (ya aplicado en prod 2026-08-25):
```sql
ALTER TABLE daily_cut_vendor_counts
  ADD COLUMN terminals_manual JSON NULL AFTER usd_exchange_rate,
  ADD COLUMN transfer_manual DECIMAL(15,4) NOT NULL DEFAULT 0 AFTER terminals_manual,
  ADD COLUMN cheque_manual DECIMAL(15,4) NOT NULL DEFAULT 0 AFTER transfer_manual;
```

**FTP** (pendiente):
- `app/Http/Controllers/DailyCutController.php` — `weekly()` calcula `vendor_total_manual`; `saveVendorCounts()` acepta terminales/transfer/cheque manuales; row de denominations incluye `expenses`
- `app/DailyCutVendorCount.php` — 3 columnas nuevas + helpers `totalManualMxn()` y `terminalsManualSum()`
- `resources/views/daily_cut/weekly.blade.php` — nueva estructura por día, gris con "sin captura del cajero" cuando `vendor_total_manual == 0`
- `resources/views/daily_cut/denominations.blade.php` — inputs manuales del cajero
- `resources/views/daily_cut/denominations_backup.blade.php` — **nuevo**, backup de la versión vieja por si prefieren volver (no accesible por URL, solo por rename manual)

### Verificación

1. `/daily-cuts/weekly?start_date=…&location_id=8` — cada día muestra la nueva estructura completa
2. `/daily-cuts/denominations?location_id=6` con un día que tenga datos:
   - TOTAL EFECTIVO muestra bruto (no menos cambio)
   - Fila del cajero permite escribir BanBajio/Banorte/Banamex/Transfer/Cheque
   - Rate USD muestra su input arriba y resultado ($USD × rate) debajo
   - Botón "Guardar cajero" → toast verde, persiste al refrescar

### Backup rollback

Si algo sale mal y prefieren la versión vieja:
1. En el server, renombrar `denominations.blade.php` → `denominations_new.blade.php`
2. Renombrar `denominations_backup.blade.php` → `denominations.blade.php`
3. Sin cambios en controllers ni SQL (todo backward compatible con el schema viejo)

---

## Bloque C — Módulo REPARACIÓN DE TIENDA (nuevo)

### Por qué

Módulo pedido por el user para registrar reparaciones internas que son **gratis para el cliente** (no se cobra) donde el técnico comisiona. Reglas:

- **NO crea transactions** — vive en su propia tabla `store_repairs`
- **NO aparece en ningún reporte de ventas**, cortes, dashboard, denominaciones ni garantías
- Solo se integra con el reporte de técnicos (columna aparte "N rep. de tienda (+\$X)")
- Al crear: comisión = 0. Admin/gerente puede actualizarla después
- IMEI autocompleta desde ventas y **prellena cliente + factura + sucursal** automáticamente
- Cliente opcional (walk-in permitido)

### Permisos

- `celfix.store_repairs.access` — ver el listado (vendedores también)
- `celfix.store_repairs.manage_commissions` — editar el campo comisión
- Agregar reparaciones: solo `business_settings.access` o `superadmin`

### Schema (SQL para prod)

```sql
-- 1) Tabla nueva
CREATE TABLE IF NOT EXISTS store_repairs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id INT UNSIGNED NOT NULL,
  location_id INT UNSIGNED NOT NULL,
  imei VARCHAR(191) NOT NULL,
  technician_id BIGINT UNSIGNED NOT NULL,
  commission DECIMAL(15,4) NOT NULL DEFAULT 0,
  customer_name VARCHAR(191) NULL,
  customer_mobile VARCHAR(50) NULL,
  notes TEXT NULL,
  status ENUM('pending','in_progress','delivered','cancelled') NOT NULL DEFAULT 'pending',
  delivered_at TIMESTAMP NULL,
  created_by INT UNSIGNED NOT NULL,
  updated_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_bl_st (business_id, location_id, status),
  INDEX idx_tech (technician_id),
  INDEX idx_created (created_at)
) DEFAULT CHARSET=utf8mb4;

-- 2) Marca de referencia (opcional, no la usa el módulo)
INSERT INTO brands (business_id, name, description, created_by, created_at, updated_at)
SELECT 2, 'REPARACION DE TIENDA',
  'Marca interna. NO aparece en reportes de ventas — solo referencia para el módulo Reparación de Tienda.',
  (SELECT id FROM users WHERE business_id = 2 ORDER BY id LIMIT 1),
  NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM brands WHERE business_id = 2 AND name = 'REPARACION DE TIENDA');

-- 3) Permisos
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'celfix.store_repairs.access', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'celfix.store_repairs.access');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'celfix.store_repairs.manage_commissions', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'celfix.store_repairs.manage_commissions');

-- 4) Asignar a admin/superadmin
INSERT INTO role_has_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.business_id = 2
  AND (r.name LIKE '%dmin%' OR r.name LIKE '%uper%')
  AND p.name IN ('celfix.store_repairs.access', 'celfix.store_repairs.manage_commissions')
  AND NOT EXISTS (SELECT 1 FROM role_has_permissions rhp
                  WHERE rhp.role_id = r.id AND rhp.permission_id = p.id);
```

### Verificación

```sql
SHOW TABLES LIKE 'store_repairs';
SELECT id, name FROM brands WHERE business_id = 2 AND name = 'REPARACION DE TIENDA';
SELECT id, name FROM permissions WHERE name LIKE 'celfix.store_repairs.%';
SELECT r.name AS rol, p.name AS permiso
FROM role_has_permissions rhp
JOIN roles r ON r.id = rhp.role_id
JOIN permissions p ON p.id = rhp.permission_id
WHERE r.business_id = 2 AND p.name LIKE 'celfix.store_repairs.%'
ORDER BY r.name, p.name;
```

### Deploy — Archivos por FTP

**Nuevos:**
- `app/StoreRepair.php` — modelo con relaciones + constants STATUSES
- `app/Http/Controllers/StoreRepairController.php` — CRUD + endpoints `searchImei` y `lookupImei`
- `resources/views/store_repair/index.blade.php` — listado con DataTable + filtros
- `resources/views/store_repair/create.blade.php` — form con IMEI autocomplete + auto-fill de cliente/factura/sucursal
- `resources/views/store_repair/edit.blade.php` — editar (comisión oculta si no tienes permiso)

**Editados:**
- `routes/web.php` — 2 rutas GET nuevas + `Route::resource('store-repairs')`
- `app/Http/Middleware/AdminSidebarMenu.php` — nueva sección "Reparación de Tienda" con 2 subitems, order(34)
- `resources/views/role/partials/celfix_permissions.blade.php` — 2 checkboxes nuevos
- `app/Http/Controllers/TechnicianController.php` — `buildReportData()` calcula `store_repair_commission` y `store_repair_count` por técnico y los suma a `commission_due`
- `resources/views/technician/report.blade.php` — label teal "N rep. de tienda (+\$X)" en header + fila detallada en tabla semanal

### Endpoints internos

- `GET /store-repairs` — listado (DataTable ajax también en la misma URL)
- `GET /store-repairs/create` — form nuevo
- `POST /store-repairs` — guarda
- `GET /store-repairs/{id}/edit` — form editar
- `PUT /store-repairs/{id}` — actualiza
- `DELETE /store-repairs/{id}` — soft-cancel (marca status='cancelled')
- `GET /store-repairs/search-imei?term=X` — autocompleta IMEIs de ventas + reparaciones previas
- `GET /store-repairs/lookup-imei?imei=X` — busca info completa: producto, cliente, factura, sucursal desde la venta original

### Verificación en app

1. Sidebar muestra "Reparación de Tienda" con 2 subitems
2. `/store-repairs/create`:
   - Escribe 3+ caracteres del IMEI → sugerencias
   - Al elegir uno vendido → caja verde con producto/cliente/factura, sucursal se pre-selecciona, nombre y teléfono se llenan
   - Si es IMEI externo → caja amarilla "captura manual"
3. Guarda → aparece en `/store-repairs` con comisión $0
4. Editar → cambiar comisión a $100 (solo admin)
5. `/technicians/report` — header del técnico muestra "1 rep. de tienda (+$100)" en teal, y "Comisión neta: $100"

### Regla de IMEI único

IMEI es único **solo si hay una reparación activa** (`pending` o `in_progress`) con ese IMEI. Si ya se entregó o canceló, puede volver a entrar (el mismo equipo puede necesitar más reparaciones).

---

## Después del deploy

Actualizar el memory `pending-prod-deploys.md` (en `~/.claude/…/memory/`) para reflejar el estado nuevo. Idealmente vaciarlo o dejar solo lo que quedó pendiente.

---

## Contactos

- **Mantenedor:** José Luis Gómez
- **Business:** Celfix Mexicali (business_id=2 en prod)
- **Fecha del paquete:** 2026-08-25
