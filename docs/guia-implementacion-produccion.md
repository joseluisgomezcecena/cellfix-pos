# Guía de implementación en producción — Celfix POS

**Para:** administrador del servidor (aaPanel)
**Tiempo estimado:** 30-45 minutos (incluyendo backup y verificación)
**Riesgo:** bajo si se siguen los pasos en orden. El SQL es 100% idempotente, no rompe nada existente.

---

## 0. Antes de empezar — checklist

- [ ] **Backup completo** de la base de datos de producción.
- [ ] **Backup** de la carpeta `/public_html` o equivalente (al menos `app/`, `resources/`, `routes/`, `database/`, `lang/`, `public/js/`, `docs/`).
- [ ] Acceso a phpMyAdmin de producción.
- [ ] Acceso a aaPanel → File Manager.
- [ ] Lista de archivos modificados (sección 2 de esta guía).
- [ ] (Opcional) Acceso a Terminal de aaPanel para configurar cron.

---

## 1. Backup de la base de datos

### En phpMyAdmin:

1. Seleccionar la BD de producción.
2. Pestaña **Exportar** → método **Personalizado**.
3. Selecciona todas las tablas.
4. Formato: **SQL**.
5. Opciones:
   - ☑ Estructura
   - ☑ Datos
   - ☑ Añadir `DROP TABLE / VIEW / etc.` si existe
6. Comprimir: **gzip** (más rápido de subir/bajar).
7. Botón **Continuar** → descarga el archivo `.sql.gz`.
8. **Guárdalo con fecha en el nombre**: `backup_celfix_2026-06-05_antes_deploy.sql.gz`.

> ⚠️ **NO continúes** si el backup falla o queda corrupto.

---

## 2. Subir archivos al servidor

### Archivos a subir (vía aaPanel → File Manager → reemplazar):

#### Controladores (modificados)
- `app/Http/Controllers/DailyCutController.php`
- `app/Http/Controllers/RepairOrderController.php`
- `app/Http/Controllers/ReportController.php`
- `app/Http/Controllers/SalesDashboardController.php`
- `app/Http/Controllers/TechnicianController.php`
- `app/Http/Controllers/VendorReportController.php`

#### Middleware (modificados)
- `app/Http/Middleware/AdminSidebarMenu.php`
- `app/Http/Middleware/EnsureDailyCutAt6PM.php`
- `app/Http/Kernel.php`

#### Controladores nuevos
- `app/Http/Controllers/StockBulkAdjustController.php` ⬅️ **NUEVO**
- `app/Exports/StockBulkAdjustTemplateExport.php` ⬅️ **NUEVO**
- `app/Exports/WeeklyVendorReportExport.php` (modificado)

#### Rutas (modificado)
- `routes/web.php`

#### Vistas (modificadas)
- `resources/views/layouts/app.blade.php`
- `resources/views/business/settings.blade.php`
- `resources/views/daily_cut/index.blade.php`
- `resources/views/daily_cut/weekly.blade.php`
- `resources/views/daily_cut/denominations.blade.php`
- `resources/views/technician/report.blade.php`
- `resources/views/vendor_report/weekly.blade.php`
- `resources/views/sales_dashboard/index.blade.php`
- `resources/views/report/stock_report.blade.php`

#### Vistas nuevas
- `resources/views/repair_order/admin_index.blade.php` ⬅️ **NUEVO**
- `resources/views/stock_bulk_adjust/index.blade.php` ⬅️ **NUEVO**

#### Otros
- `public/js/report.js`
- `lang/es/lang_v1.php`
- `Modules/Layaway/Http/Controllers/DataController.php`

> 💡 **Truco rápido**: descomprime el zip del repo en una carpeta local, y arrástralos por carpetas a aaPanel File Manager con la opción "reemplazar". Verifica primero que la fecha de los archivos sea de hoy.

### Permisos
Después de subir, verifica que los archivos tengan los permisos correctos:
- Carpetas: `755`
- Archivos PHP/HTML: `644`

Si hay archivos con permisos raros (000, 777), corrígelos.

---

## 3. Limpiar caches que persisten en disco

En **aaPanel → File Manager**, elimina los archivos:

- `bootstrap/cache/config.php` (si existe)
- `bootstrap/cache/routes-v7.php` (si existe)
- `bootstrap/cache/services.php` (si existe — esto Laravel lo regenera solo)
- Todos los archivos dentro de `storage/framework/views/` (las vistas Blade compiladas)

> No borres `bootstrap/cache/.gitignore` ni la carpeta misma. Solo los archivos dentro.

Esto fuerza a Laravel a re-cachear las rutas, configuración y vistas con el código nuevo en la siguiente request.

---

## 4. Aplicar el SQL de base de datos

### En phpMyAdmin:

1. Selecciona la BD de producción.
2. Pestaña **Importar** → **Seleccionar archivo** → sube `docs/deploy_produccion.sql`.
3. Formato: SQL.
4. Codificación: utf8.
5. Botón **Continuar**.

> Si el archivo no se puede importar (por tamaño o restricciones), alternativa:
> Pestaña **SQL** → pega el contenido completo del archivo → **Continuar**.

### Qué hace el SQL (77 statements, 100% idempotente)

**9 tablas nuevas** (solo si no existen):

| Tabla | Para qué |
|---|---|
| `card_terminals` | Terminales de tarjeta (banco, cuenta) |
| `daily_cuts` | Cortes diarios por sucursal |
| `discount_locations` | Descuentos asignados por sucursal |
| `repair_product_commissions` | Comisión por producto de reparación |
| `sales_goals` | Metas de venta semanales |
| `stock_corrections` | Correcciones de inventario (Entrada/Salida) |
| `technicians` | Técnicos del taller |
| `technician_locations` | Asignación técnico ↔ sucursal |
| `vendor_commission_targets` | Metas y comisiones por vendedor × marca |

**9 columnas nuevas** (con guard idempotente):

| Tabla | Columna | Tipo |
|---|---|---|
| `business` | `cash_exchange_rate` | `decimal(10,4)` default 18.0 |
| `products` | `reward_points` | `int NULL` |
| `transaction_payments` | `card_terminal_id` | `bigint NULL` + índice |
| `transaction_payments` | `denomination_breakdown` | `json NULL` |
| `transaction_sell_lines` | `technician_id` | `bigint NULL` + índice + FK |
| `transaction_sell_lines` | `repair_entry_date` | `date NULL` |
| `transaction_sell_lines` | `repair_anticipo` | `decimal(22,4) NULL` |
| `transaction_sell_lines` | `technician_commission_override` | `decimal(22,4) NULL` |
| `transactions` | `repair_status` | `varchar(20) NULL` + índice |

**1 limpieza de datos**:
- `DELETE FROM brands WHERE name LIKE '=%' AND no_referenciada` (marcas creadas por error con fórmulas de Excel).

### Resultado esperado
phpMyAdmin debe mostrar algo como:
```
La consulta tardó 0.0421 segundos
77 filas afectadas
```

Si dice **algún error**, NO sigas. Salta al paso 8 (Rollback).

---

## 5. Verificar la BD post-deploy

Pega en phpMyAdmin → pestaña **SQL** sobre la BD de prod:

```sql
SELECT
  (SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name IN ('card_terminals','daily_cuts','discount_locations',
                         'repair_product_commissions','sales_goals','stock_corrections',
                         'technicians','technician_locations','vendor_commission_targets')) AS tablas,
  (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND (
        (table_name='business' AND column_name='cash_exchange_rate') OR
        (table_name='products' AND column_name='reward_points') OR
        (table_name='transaction_payments' AND column_name IN ('card_terminal_id','denomination_breakdown')) OR
        (table_name='transaction_sell_lines' AND column_name IN ('technician_id','repair_entry_date','repair_anticipo','technician_commission_override')) OR
        (table_name='transactions' AND column_name='repair_status')
      )) AS columnas;
```

**Resultado esperado:** `tablas = 9` y `columnas = 9`.

- ❌ Si te da menos → falta algo. Revisa los logs de phpMyAdmin y re-corre el SQL.
- ✅ Si te da 9/9 → todo aplicado correctamente.

---

## 6. Validación funcional en el POS

Refresca el sistema con **Ctrl+Shift+R** (hard refresh) y prueba las nuevas funciones:

### 6.1 Menú lateral — debe verse así

| Sección | Items | Color |
|---|---|---|
| Vender (30) | …, **Sales Dashboard** | azul |
| Cortes Diarios (31) | **Cortes Diarios** | azul |
| Vendedores (32) | **Reporte Semanal**, **Metas y Comisiones** | azul |
| Técnicos (33) | **Técnicos**, **Reporte de Técnicos**, **Administrar Reparaciones** | azul |
| Configuración | …, **Terminales de Tarjeta**, **Ajuste Masivo de Stock** | azul |
| Apartado | …, **Equipos Apartados** | azul |
| Ajuste de Stock | …, **Corrección de Inventario** | azul |

### 6.2 Validar cada función nueva

| Función | URL | Cómo validar |
|---|---|---|
| Corte automático 6 PM | Menú → Cortes Diarios | Ver el banner: "Corte automático programado…" o "Corte automático generado hoy a las …" |
| Reparaciones admin | Menú → Técnicos → Administrar Reparaciones | Buscar una reparación, abrir modal "Cambiar técnico" |
| Ajuste masivo stock | Menú → Configuración → Ajuste Masivo de Stock | Descargar plantilla para una sucursal, abrir el Excel |
| Override comisión técnico | `/technicians/report` | Click en ✏️ junto a un pago, modal abre |
| Stock report filtro | `/reports/stock-report` | Dropdown "Filtrar existencias" después de "Unidad" |
| Semana sábado-viernes | `/vendor-reports/weekly` o `/technicians/report` | Datepicker abre, solo deja seleccionar sábados |
| Repair status | POS → botón "Recibir reparación" (naranja) | Botón visible, abre el modal de pago |

---

## 7. Configuración opcional — Cron exacto a las 18:00

Si quieres que el corte se genere exactamente a las 18:00 incluso sin tráfico de usuarios:

### 7.1 Generar token aleatorio

En aaPanel → Terminal:
```bash
php -r "echo bin2hex(random_bytes(32));"
```
Te da algo como `a3f9c8d2b1e4f7a6c5d8b9e2f1a4c7d8b1e4f7a6c5d8b9e2f1a4c7d8b1e4f7a6`.

### 7.2 Agregar al `.env` de producción

aaPanel → File Manager → editar `.env` → al final:
```env
DAILY_CUT_CRON_TOKEN=a3f9c8d2b1e4f7a6c5d8b9e2f1a4c7d8b1e4f7a6c5d8b9e2f1a4c7d8b1e4f7a6
```

### 7.3 Borrar `bootstrap/cache/config.php` si existe (Laravel re-lee `.env`)

### 7.4 Verificar zona horaria del servidor

aaPanel → Terminal:
```bash
date
```
- Si dice `UTC` → cron debe correr a las **00:00 UTC** (que es 6 PM México).
- Si dice `CST` o América/México_City → cron debe correr a las **18:00**.

Si necesitas cambiar timezone:
```bash
timedatectl set-timezone America/Mexico_City
```

### 7.5 Crear el cron en aaPanel

aaPanel → **Cron** → **Add Task**:

| Campo | Valor |
|---|---|
| Task Type | Shell Script |
| Name | `Corte diario Celfix 18:00` |
| Period | Day |
| Time | `18:00` (o `00:00` si UTC) |
| User | `www` (o el usuario del sitio) |
| Script | (ver abajo) |

Script:
```bash
curl -sS -A "celfix-cron" -m 120 \
  "https://celfix.mx/cron/daily-cuts/auto-generate?token=a3f9c8d2b1e4f7a6c5d8b9e2f1a4c7d8b1e4f7a6c5d8b9e2f1a4c7d8b1e4f7a6"
```

> **Importante:** sustituye el dominio y el token por los tuyos.

### 7.6 Probar el cron

En aaPanel → Cron → fila del nuevo task → botón **Execute** (▶).

La respuesta esperada (en el log del cron):
```json
{"success":true,"businesses":1,"date":"2026-06-05"}
```

- ✅ Éxito → ve a `/daily-cuts`, debe aparecer el banner verde "Corte automático generado hoy".
- ❌ `403 Forbidden` → token incorrecto. Revisa `.env` y borra `bootstrap/cache/config.php`.

> 🛡️ **El cron es OPCIONAL**. Si no lo configuras, el sistema sigue funcionando con el "heartbeat" — el primer usuario que entre al sistema después de las 6 PM dispara el corte automáticamente.

---

## 8. Plan de Rollback (si algo sale mal)

### Si el SQL falló o causó problemas
1. phpMyAdmin → Importar → sube el archivo `backup_celfix_2026-06-05_antes_deploy.sql.gz`.
2. ⚠️ Esto **restaura la BD al estado pre-deploy** (perderías cualquier venta o cambio hecho entre el backup y ahora).
3. Si solo quieres revertir un cambio específico, abre el backup en un editor y copia solo las definiciones de tabla afectadas.

### Si los archivos PHP causan error 500
1. Restaura desde tu backup de archivos.
2. Limpia los caches de Laravel:
   - Borra `bootstrap/cache/config.php`
   - Borra `bootstrap/cache/routes-v7.php`
   - Vacía `storage/framework/views/`
3. Hard refresh en el browser (Ctrl+Shift+R).

### Si solo un módulo nuevo no funciona pero el resto sí
- Casi seguro es **vista compilada antigua** → vacía `storage/framework/views/`.
- O **rutas cacheadas antiguas** → borra `bootstrap/cache/routes-v7.php`.

---

## 9. Logs útiles para depurar

Ubicación: `storage/logs/laravel-YYYY-MM-DD.log`

Patrones a buscar:
- `[daily-cut-heartbeat]` — corte automático
- `[daily-cut-cron]` — endpoint cron externo
- `[stock-bulk-adjust]` — importación masiva de stock
- `ERROR` — cualquier error fatal

```bash
# Ver últimas 50 líneas del log de hoy
tail -50 storage/logs/laravel-$(date +%Y-%m-%d).log

# Buscar errores hoy
grep "ERROR" storage/logs/laravel-$(date +%Y-%m-%d).log
```

---

## 10. Resumen — checklist post-deploy

- [ ] Backup tomado y guardado con fecha
- [ ] Archivos subidos al servidor
- [ ] Caches limpiados (`bootstrap/cache/*`, `storage/framework/views/`)
- [ ] SQL ejecutado en phpMyAdmin (77 statements, 0 errores)
- [ ] Validación SQL devuelve `tablas = 9, columnas = 9`
- [ ] Hard refresh y validación visual del menú (links azules en su lugar)
- [ ] Probada al menos UNA función de cada módulo nuevo
- [ ] `.env` actualizado con `DAILY_CUT_CRON_TOKEN` (opcional)
- [ ] Cron de aaPanel configurado y probado (opcional)
- [ ] Sin errores nuevos en `storage/logs/laravel-*.log`

---

## Soporte rápido

| Problema | Solución más común |
|---|---|
| Error 500 después del deploy | Vaciar `storage/framework/views/` |
| Menú sigue viéndose viejo | Hard refresh (Ctrl+Shift+R) o borrar `bootstrap/cache/routes-v7.php` |
| `Class StockBulkAdjustController not found` | El archivo no se subió o está en mal path. Verifica `app/Http/Controllers/` |
| Botón "Recibir reparación" no aparece en POS | Vaciar caches (vistas + rutas) |
| Cortes no se generan | Verificar `.env` timezone, ver `storage/logs/laravel-*.log` filtrando por `[daily-cut-` |
| Marcas "= …" volvieron a aparecer | El SQL las borra solo si no están referenciadas; si tienen productos vinculados se mantienen. Eliminar manualmente. |
