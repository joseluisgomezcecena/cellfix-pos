# Deploy pendiente a producción — 2026-09-29

Documento autosuficiente con lo que falta subir a `pos.celfix.mx` según el estado real de la BD de prod verificado el 2026-09-29. Diseñado para pegarse en una sesión nueva de Claude sin contexto previo.

---

## Contexto del proyecto

- **Celfix POS** — Sistema Point-of-Sale basado en UltimatePOS + módulos custom para una cadena de retail de celulares en Mexicali, MX (4 sucursales + almacén).
- **Business ID en BD:** `2`
- **Hosting:** cPanel/BT-Panel (Ubuntu 16GB en Hetzner). **Sin CLI/artisan disponible.** Deploy = subir archivos por FTP + correr SQL a mano en phpMyAdmin. No hay `composer install`, no hay migrations automáticas.
- **BD de prod:** `pos_celfix_mx` (mismo servidor MySQL local del hosting)
- **Repo Git:** `feature/pos-improved` y `master` (fast-forward). Todo lo que está commiteado ya está en GitHub.
- **Working dir local:** `c:\xampp\htdocs\pos.celfix.mx.dev`

## Alcance

Este deploy cubre **solo cambios del POS** (backend Laravel usado por las sucursales). Excluye:
- API v1 para la app Flutter (`/api/v1/auth/*`, `/me`, `/purchases`, etc.)
- WhatsApp Service para recuperación de contraseña
- Columnas `contacts.{membership_no, membership_expires_at, app_password, app_api_token}` (para la app)

Todo eso se despliega en otro paquete separado cuando se arranque el rollout de la app Flutter.

---

## Estado real de la BD de prod (verificado 2026-09-29)

### ✅ YA aplicado

| Elemento | Estado |
|---|---|
| Tabla `store_repairs` | ✅ EXISTE |
| Marca "REPARACION DE TIENDA" en `brands` | ✅ EXISTE (id=114) |
| Columnas `daily_cut_vendor_counts.{terminals_manual, transfer_manual, cheque_manual}` | ✅ EXISTEN (3/3) |
| Tabla `inventory_transfers` (módulo InventoryMultiLocation) | ✅ EXISTE (13 columnas) |
| Tabla `inventory_transfer_items` | ✅ EXISTE (11 columnas) |
| Permisos `inventory_multi.*` | ✅ 5 permisos únicos (con duplicados históricos cosméticos que se pueden ignorar) |
| Permisos `celfix.*` | ✅ 11 de 12 |
| Rol "Administrador General#2" | ✅ Ya tiene todos los permisos celfix |

### ❌ FALTA por aplicar

| Elemento | Estado |
|---|---|
| Tabla `app_promos` | ❌ FALTA |
| Tabla `app_benefits` | ❌ FALTA |
| 5 columnas en `business_locations` (para App Config) | ❌ FALTAN (is_public_in_app, hours_json, latitude, longitude, phone_app) |
| Permiso `celfix.app_config.access` | ❌ FALTA |
| Asignación de permisos celfix al rol "Admin#2" | ⚠️ Solo tiene 3 de 12 |

---

## Paquete SQL (correr en phpMyAdmin de prod)

El SQL usa nombres de BD prefijados (`pos_celfix_mx.tabla`) para funcionar sin importar la BD activa en la sesión. Es idempotente donde es posible.

**Ejecutar en orden:**

```sql
-- ═══════════════════════════════════════════════════════════════
-- 1) 5 columnas nuevas en business_locations (para App Config)
-- ═══════════════════════════════════════════════════════════════
ALTER TABLE pos_celfix_mx.business_locations
  ADD COLUMN is_public_in_app TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN hours_json JSON NULL,
  ADD COLUMN latitude DECIMAL(10,7) NULL,
  ADD COLUMN longitude DECIMAL(10,7) NULL,
  ADD COLUMN phone_app VARCHAR(50) NULL;


-- ═══════════════════════════════════════════════════════════════
-- 2) Tabla app_promos (promociones que consume la app Flutter)
-- ═══════════════════════════════════════════════════════════════
CREATE TABLE pos_celfix_mx.app_promos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id INT UNSIGNED NOT NULL,
  target_location_id INT UNSIGNED NULL,
  title VARCHAR(191) NOT NULL,
  description TEXT NULL,
  category VARCHAR(100) NULL,
  image_path VARCHAR(255) NULL,
  starts_at DATE NULL,
  ends_at DATE NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT UNSIGNED NULL,
  updated_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_biz_active (business_id, is_active),
  INDEX idx_target (target_location_id)
) DEFAULT CHARSET=utf8mb4;


-- ═══════════════════════════════════════════════════════════════
-- 3) Tabla app_benefits (beneficios de membresía)
-- ═══════════════════════════════════════════════════════════════
CREATE TABLE pos_celfix_mx.app_benefits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id INT UNSIGNED NOT NULL,
  target_location_id INT UNSIGNED NULL,
  title VARCHAR(191) NOT NULL,
  description TEXT NULL,
  value_type ENUM('percentage','fixed','text') NOT NULL DEFAULT 'percentage',
  value DECIMAL(15,4) NULL,
  value_text VARCHAR(191) NULL,
  min_purchase DECIMAL(15,4) NULL,
  conditions TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT UNSIGNED NULL,
  updated_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_biz_active (business_id, is_active),
  INDEX idx_target (target_location_id)
) DEFAULT CHARSET=utf8mb4;


-- ═══════════════════════════════════════════════════════════════
-- 4) Permiso celfix.app_config.access
-- ═══════════════════════════════════════════════════════════════
INSERT INTO pos_celfix_mx.permissions (name, guard_name, created_at, updated_at)
VALUES ('celfix.app_config.access', 'web', NOW(), NOW());


-- ═══════════════════════════════════════════════════════════════
-- 5) Asignar TODOS los permisos celfix.* a los roles admin.
--    Incluye el nuevo celfix.app_config.access y los 8 que faltan
--    en Admin#2. NOT EXISTS evita duplicados en Administrador General#2.
-- ═══════════════════════════════════════════════════════════════
INSERT INTO pos_celfix_mx.role_has_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM pos_celfix_mx.roles r
CROSS JOIN pos_celfix_mx.permissions p
WHERE r.business_id = 2
  AND (r.name LIKE '%dmin%' OR r.name LIKE '%uper%')
  AND p.name LIKE 'celfix.%'
  AND NOT EXISTS (
    SELECT 1 FROM pos_celfix_mx.role_has_permissions rhp
    WHERE rhp.role_id = r.id AND rhp.permission_id = p.id
  );
```

### Verificación post-SQL

```sql
-- Debe salir 12
SELECT COUNT(*) AS total_permisos_celfix FROM pos_celfix_mx.permissions WHERE name LIKE 'celfix.%';

-- Ambos roles deben tener 12
SELECT r.name AS rol, COUNT(DISTINCT p.id) AS permisos_celfix_asignados
FROM pos_celfix_mx.roles r
LEFT JOIN pos_celfix_mx.role_has_permissions rhp ON rhp.role_id = r.id
LEFT JOIN pos_celfix_mx.permissions p ON p.id = rhp.permission_id AND p.name LIKE 'celfix.%'
WHERE r.business_id = 2 AND (r.name LIKE '%dmin%' OR r.name LIKE '%uper%')
GROUP BY r.id, r.name;

-- Debe salir 2 filas
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA='pos_celfix_mx' AND TABLE_NAME IN ('app_promos','app_benefits');

-- Debe salir 5 filas
SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA='pos_celfix_mx' AND TABLE_NAME='business_locations'
  AND COLUMN_NAME IN ('is_public_in_app','hours_json','latitude','longitude','phone_app');
```

---

## Archivos por FTP (21 archivos)

Todos los paths son relativos a `/www/wwwroot/pos.celfix.mx/` en el servidor.

### 🆕 NUEVOS a subir (12)

**Módulo App Config (admin para gestionar sucursales/promos/beneficios):**
```
app/AppPromo.php
app/AppBenefit.php
app/Http/Controllers/AppConfig/LocationController.php
app/Http/Controllers/AppConfig/PromoController.php
app/Http/Controllers/AppConfig/BenefitController.php
resources/views/app_config/locations/index.blade.php
resources/views/app_config/locations/edit.blade.php
resources/views/app_config/promos/index.blade.php
resources/views/app_config/promos/form.blade.php
resources/views/app_config/benefits/index.blade.php
resources/views/app_config/benefits/form.blade.php
```

**Backup para rollback de denominaciones:**
```
resources/views/daily_cut/denominations_backup.blade.php
```

### ✏️ EDITADOS a reemplazar (9)

Aunque algunos de estos archivos ya se subieron previamente con otros bloques (por ejemplo con el módulo Store Repair), el repo tiene versiones más nuevas con cambios adicionales acumulados. Siempre sube la versión más reciente del git.

```
app/DailyCutVendorCount.php                                  Columnas manuales cajero + helpers
app/Utils/TransactionUtil.php                                Fix reparaciones "en limbo" al editar venta
app/Utils/ProductUtil.php                                    Stock history cross-sucursal para IMEIs
app/Utils/DailyCutUtil.php                                   Ajustes daily cut
app/Http/Controllers/ProductController.php                   Fix iPhone warranty stock revert
app/Http/Controllers/SellPosController.php                   Fix cotización sin desglose de billetes
app/Http/Controllers/WarrantyClaimController.php             Fix modal "Ver" garantía
app/Http/Controllers/SalesDashboardController.php            Fix equipos + permiso executive
app/Http/Controllers/DailyCutController.php                  Rediseño weekly + denominations + vendor counts
```

**Multi-editados (contienen App Config además de Store Repair — hay que resubir):**
```
app/Http/Middleware/AdminSidebarMenu.php                     Agrega dropdown "App Config"
routes/web.php                                               Agrega rutas /app-config/*
resources/views/role/partials/celfix_permissions.blade.php   Agrega checkbox App Config
```

**Views de mejoras UI/UX:**
```
resources/views/daily_cut/weekly.blade.php                   Rediseño completo (SUBTOTAL DINERO, DINERO POR VENDEDOR)
resources/views/daily_cut/denominations.blade.php            Rediseño + inputs manuales del cajero
resources/views/product/stock_history_details.blade.php      Columna Sucursal (viaje del IMEI)
```

**Módulo InventoryMultiLocation:**
```
Modules/InventoryMultiLocation/Http/Controllers/InventoryController.php
Modules/InventoryMultiLocation/Resources/views/partials/inventory_table.blade.php
Modules/InventoryMultiLocation/Resources/lang/es/lang.php
Modules/InventoryMultiLocation/Resources/lang/en/lang.php
```

Esos 4 archivos del módulo agregan la columna "Último Movimiento" en el listado de inventario multi-sucursal, con badge de tipo (compra/venta/transfer/garantía/store_repair) + fecha + referencia. Solo cambios de código, no requieren SQL.

---

## Orden de deploy recomendado

1. **Backup completo de la BD** desde phpMyAdmin (export → SQL → gzip). Guárdalo en tu máquina antes de tocar nada. Toma ~2 min.

2. **Corre el SQL** (las 5 secciones en orden). Si falla algo en medio:
   - Los ALTER de business_locations pueden fallar si ya se corrieron antes (verifica con la query del bloque de verificación previa)
   - Los CREATE TABLE fallarán si la tabla ya existe — cambia `CREATE TABLE` por `CREATE TABLE IF NOT EXISTS`
   - Los INSERT permissions fallarán por nombre único si ya existe — cambia por el patrón `INSERT ... WHERE NOT EXISTS`

3. **Sube los archivos por FTP** — todos juntos en la misma sesión de FTP para atomicidad. Elige horario de bajo tráfico (ideal después de 9pm o antes de 8am).

4. **Borra cache de Laravel** por FTP si existe:
   - `bootstrap/cache/config.php`
   - `bootstrap/cache/routes-v7.php`
   - `bootstrap/cache/services.php`

5. **Pruebas manuales** (en el POS con user admin):
   - `/pos/create` carga sin errores → **fix cotización OK**
   - `/sells` con una reparación → editar → guardar → técnico y anticipo se conservan
   - `/inventory-multi/inventory` muestra columna nueva **"Último Movimiento"** con badges
   - `/products/stock-history/{id_equipo_imei}` muestra columna **"Sucursal"** y movimientos entre sucursales
   - `/daily-cuts/weekly?start_date=...&location_id=X` muestra nueva estructura (SUBTOTAL DINERO, DINERO POR VENDEDOR, DIFERENCIA en gris si no hay captura)
   - `/daily-cuts/denominations?location_id=X` muestra inputs manuales del cajero para BanBajio/Banorte/Banamex/Transfer/Cheque
   - **Sidebar** muestra nuevo dropdown **"App Config"** con 3 subitems (Sucursales/Promos/Beneficios)
   - `/store-repairs` funciona (ya estaba en prod)
   - `/technicians/report` muestra label teal "N rep. de tienda (+$X)" en cada técnico
   - `/warranty-claims` → botón "Ver" abre modal con detalle (fix modal cargando JSON crudo)

---

## Cambios de código clave (contexto para diagnóstico)

Si algo se rompe después del deploy, aquí el contexto rápido de qué cambió en cada archivo importante:

### `app/Utils/TransactionUtil.php`
**Fix reparaciones en limbo.** En el método `editSellLine()`, cambió `!empty($product['technician_id'])` por `array_key_exists('technician_id', $product)` para los campos `technician_id`, `repair_entry_date`, `repair_anticipo`. Antes se sobreescribían con NULL cuando el form del POS normal (`/sells/edit`) editaba una venta que era reparación.

### `app/Utils/ProductUtil.php`
**Stock history cross-sucursal para IMEIs + warranty/store_repair.** El método `getVariationStockHistory()`:
- Detecta si el `sub_sku` es IMEI (14-20 dígitos numéricos) — si sí, NO filtra por `location_id` (muestra el viaje entre sucursales)
- Agrega movimientos de `warranty_claims` (replacement_variation_id = X, quantity_change = -1)
- Agrega movimientos de `store_repairs` (por IMEI, quantity_change = 0, solo evento)
- Re-ordena por fecha y recalcula el stock progresivo
- Nueva columna `location_id` y `location_name` en cada línea del array de resultado

También en la vista `resources/views/product/stock_history_details.blade.php` se agregó una columna "Sucursal" con badge label-primary.

### `app/Http/Controllers/ProductController.php`
**Fix iPhone warranty stock revert.** El método `productStockHistory()` YA no auto-corrige el stock desde el histórico calculado (línea 2374). Antes cuando alguien abría el historial de un iPhone que había pasado por garantía, el auto-UPDATE revertía silenciosamente el descuento. Ahora solo loguea la discrepancia con `\Log::info('[productStockHistory] discrepancia (solo aviso, no se corrige) ...')`.

### `app/Http/Controllers/SellPosController.php`
**Fix cotización sin desglose.** En los métodos `store()` y `update()` la validación de `denomination_breakdown` (que exige desglose de billetes en pagos cash) ahora solo aplica cuando `$input['status'] === 'final'`. Antes bloqueaba también cotizaciones y borradores porque el form del POS no manda desglose para cotizaciones.

### `app/Http/Controllers/WarrantyClaimController.php`
**Fix modal Ver.** En el `action` del DataTable (línea ~120), el link "Ver" cambió de `<a href="/warranty-claims/{id}">` a `<a href="#" data-href="/warranty-claims/{id}">` porque el handler global `.btn-modal` de `public/js/app.js` lee `data-href`, no `href`. Sin este fix, el modal mostraba el JSON crudo de DataTables en vez del detalle de la garantía.

### `Modules/InventoryMultiLocation/Http/Controllers/InventoryController.php`
**Columna "Último Movimiento" en `/inventory-multi/inventory`.** Nuevo método `enrichLastMovement()` que hace 2 queries UNION (transacciones + store_repairs por IMEI) por página para agregar la propiedad `last_movement` a cada fila con `{type, type_label, icon, color, date, ref}`. Consulta tablas ya existentes en prod: `purchase_lines`, `transaction_sell_lines`, `stock_adjustment_lines`, `warranty_claims`, `store_repairs`. **No requiere SQL.**

### `resources/views/daily_cut/weekly.blade.php`
**Rediseño reporte semanal.** Nueva estructura por día:
- EFECTIVO (bruto, sin restar cambio)
- TARJETA por banco (orden BANBAJIO/BANORTE/BANAMEX)
- TRANSFERENCIAS, CHEQUES
- **SUBTOTAL DINERO** (suma del sistema)
- **DINERO POR VENDEDOR** (todo lo capturado manualmente por el cajero)
- GASTOS, CAMBIO ENTREGADO
- **TOTAL DINERO** (Subtotal − Cambio − Gastos)
- **DIFERENCIA** (Vendedor − Total; verde=sobra, rojo=falta, gris=sin captura)

### `resources/views/daily_cut/denominations.blade.php`
**Inputs manuales para el cajero + TOTAL EFECTIVO bruto.** Ahora la fila del cajero permite escribir manualmente los montos de BanBajio/Banorte/Banamex/Transfer/Cheque para "empatar" cuando hay diferencia con el sistema. El TOTAL EFECTIVO ya no resta cambio (es bruto puro). Nuevas columnas CAMBIO EFECTIVO y GASTOS a la derecha. El rate USD tiene un campo de resultado abajo del input.

### `Módulo App Config`
Nuevo módulo bajo `app/Http/Controllers/AppConfig/`. 3 controllers CRUD (Location/Promo/Benefit) + 6 vistas + 2 modelos Eloquent (`AppPromo`, `AppBenefit`). El menú "App Config" aparece en el sidebar solo para usuarios con `celfix.app_config.access` (o admin). Sirve para gestionar los datos que consume la app Flutter públicamente en `/api/v1/{locations,promos,benefits}`. Las 5 columnas nuevas de `business_locations` habilitan que el admin marque una sucursal como visible en la app y capture horarios/coordenadas/teléfono especial para la app.

---

## Notas y gotchas

1. **MySQL de prod NO soporta `IF NOT EXISTS` en `ADD COLUMN`.** Solo en `CREATE TABLE`. Para hacer ALTER idempotente usar el patrón con `INFORMATION_SCHEMA` + `PREPARE stmt`. Ya está aplicado en los bloques SQL del doc.

2. **Permisos duplicados en `inventory_multi.*`**: hay 4 permisos con dos IDs cada uno (histórico). Ejemplo: `inventory_multi.view` existe con id=148 y también id=164. Spatie los tolera. Se pueden limpiar con:
   ```sql
   DELETE FROM pos_celfix_mx.role_has_permissions WHERE permission_id IN (167, 166, 165, 164);
   DELETE FROM pos_celfix_mx.permissions WHERE id IN (167, 166, 165, 164);
   ```
   Pero es cosmético, no urgente.

3. **`bootstrap/cache/`**: siempre borra el cache después de un deploy grande. Si no, los cambios en `config/`, `routes/`, o service providers pueden no aplicarse hasta la próxima invalidación.

4. **Rol "Admin#2" solo tiene 3 permisos celfix asignados**. El INSERT del bloque 5 le agrega los 8 faltantes + el nuevo `celfix.app_config.access`. Verifica con la query de post-SQL.

5. **Backup de la BD antes de tocar nada**. Los bloques 1-3 son ALTER/CREATE — reversibles pero mejor tener respaldo. Los bloques 4-5 son INSERT — más seguros.

6. **Rollback de denominations**: si el rediseño no gusta a los usuarios, se puede revertir renombrando en el server:
   - `denominations.blade.php` → `denominations_new.blade.php`
   - `denominations_backup.blade.php` → `denominations.blade.php`
   El backend es 100% compatible con el layout viejo.

---

## Referencias

- Commits en git relevantes: `6dac087` (Inventario Último Movimiento), `3bcf3f4` (WhatsApp — fuera del scope), `522a87f` (register — fuera), `152bedf` (doc — fuera), `9a73285` (deploy package 2026-08-25 — bloques A/B/C ya cubiertos), `7fc91e1` (Store Repair — ya en prod), `94a142d` (weekly+denominations), `454a675` (App Config + Sales Dashboard fix).
- Docs previos: `docs/documentacion/2026-08-25-deploy-package.md` (bloques A/B/C — la parte de Store Repair ya está aplicada según verificación 2026-09-29).
- Memory: `~/.claude/projects/c--xampp-htdocs-pos-celfix-mx-dev/memory/pending-prod-deploys.md`.

---

## Contactos

- **Mantenedor:** José Luis Gómez (joseluisgomezcecena@github)
- **Business:** Celfix Mexicali (business_id=2)
- **Fecha del paquete:** 2026-09-29
