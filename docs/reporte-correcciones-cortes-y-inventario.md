# Reporte de correcciones — Cortes diarios e inventario

**Fecha:** 2026-06-16
**Período investigado:** Cortes y ventas del 13 al 15 de junio de 2026
**Sucursal de referencia:** Sucursal Americas (location_id = 6)

---

## Resumen ejecutivo

Durante la auditoría del corte del 13/06/2026 se reportaron tres problemas:

1. Los dólares físicos contados en sucursal **no se reflejaban** en el corte diario.
2. En el reporte de técnicos varias líneas de "SERVICIO LIMPIEZA GENERAL" salían en **$0**.
3. Un iPhone 13 vendido el 13/06 **seguía apareciendo como disponible** en inventario y se tuvieron que hacer **dos correcciones manuales** para que el ajuste agarrara.

Después de analizar la base de datos transacción por transacción se identificaron **3 bugs reales en el código** y se aplicaron las correcciones. Adicionalmente se agregó una **vista imprimible** del corte para facilitar la reconciliación diaria de las cajeras.

---

## Bug #1 — El "efectivo" del corte parecía no incluir los dólares

### Lo que vio el usuario

| Reporte de denominaciones | Corte diario |
|---|---|
| MXN en cajón: $10,550 | Efectivo: $10,500 |
| USD en cajón: $120 (≈ $2,058 MXN) | (no aparecía) |
| **Total efectivo: $12,608** | **Total: $48,649** |

Parecía que los $2,058 de dólares se habían "perdido".

### La causa real (no era un bug)

`transaction_payments.amount` para pagos en efectivo con USD se guarda **ya en MXN convertido**. Por ejemplo, la factura 65915 de Luis Ayon: el cliente pagó $500 MXN + $100 USD. El sistema lo guardó como `amount = $2,215` ($500 + $100 × 17.15).

Es decir, **los dólares SÍ estaban siendo contados**, pero el total no coincidía porque:

- El reporte de denominaciones suma **bruto** (todos los billetes que pasaron por el cajón).
- El corte resta el **cambio entregado** al cliente cuando paga con más de lo que debe.

En el día del 13/06 hubo $2,108 entregados como cambio (verificado con la fórmula `final_total = amount_recibido - amount_returned` en 13 transacciones, donde el "return" era el vuelto y no una devolución de producto).

| Concepto | Monto |
|---|---|
| Efectivo bruto recibido | $12,608 |
| Cambio entregado | −$2,108 |
| **Neto en caja** | **$10,500** |
| + Tarjeta | $38,149 |
| **= Total ventas** | **$48,649** ✓ |

### Solución aplicada

Se rediseñó la vista del corte (`/daily-cuts/{id}`) para mostrar el desglose transparente:

- **Efectivo (MXN bruto)** = billetes MXN físicos recibidos
- **USD (en MXN)** = billetes USD convertidos a MXN
- **Cambio entregado** = monto restado (con signo negativo)
- **Tarjeta** = pagos con terminal
- Banner de **reconciliación** abajo que muestra la fórmula: `bruto − cambio + tarjeta + transfer + cheque = total ventas`

Esto le permite a la cajera comparar **directamente** los billetes que cuenta físicamente contra lo que el sistema dice — y si hay diferencia, identificar si fue por cambio entregado o por error de captura.

### Archivos modificados

- `resources/views/daily_cut/index.blade.php`
- `resources/views/daily_cut/show.blade.php`

---

## Bug #2 — Líneas con descuento mostraban $0 en el reporte de técnicos

### Lo que vio el usuario

En el reporte de técnicos del 13/06, varias líneas de "SERVICIO LIMPIEZA GENERAL" mostraban total en **$0** cuando físicamente sí se habían cobrado $100. Ejemplo: factura 65868 (Luis Valenzuela) — el ticket de venta mostraba el servicio en $100 (con descuento de $100 sobre $200), pero el reporte de técnicos lo listaba en $0.

### La causa raíz

Fórmula incorrecta en el código del reporte:

```php
$line_total = $unit_price_inc_tax × $quantity − $line_discount_amount;
```

**El problema:** UltimatePOS guarda `unit_price_inc_tax` **ya con el descuento aplicado**. Para esa línea concreta:
- `unit_price_inc_tax` = $100 (post-descuento)
- `quantity` = 1
- `line_discount_amount` = $100 (referencia informativa)

Cálculo con la fórmula vieja: `100 × 1 − 100 = $0` ❌

El descuento se estaba restando **dos veces**.

Esto afectaba:
- **Reporte de técnicos** (`/technicians/report`): líneas con descuento mostraban $0
- **Desglose por marca en el corte** (`/daily-cuts/{id}` → "Ventas por categoría"): las marcas con descuentos quedaban subcontadas

### Verificación del impacto

Para el cut 85 del 13/06 en sucursal Americas:

| Marca | Antes del fix | Después del fix |
|---|---|---|
| Servicios | $700 ❌ | **$1,300** ✅ |
| Reparaciones | $21,100 | $21,200 (+$100) |
| Accesorios | $8,599 | $8,899 (+$300) |

### Solución aplicada

Se cambió la fórmula en ambos lugares para usar solo `unit_price_inc_tax × quantity` (ya que ese campo contiene el precio final post-descuento):

```php
// Antes
$line_total = $unit_price_inc_tax × $quantity − $line_discount_amount;

// Después
$line_total = $unit_price_inc_tax × $quantity;
```

### Archivos modificados

- `app/Http/Controllers/TechnicianController.php` (línea 361)
- `app/Utils/DailyCutUtil.php` (línea 71)

### Nota sobre cortes ya cerrados

Los cortes generados antes del fix tienen el desglose `summary.sales_by_brand` cacheado en JSON con los números viejos. Para verlos corregidos, hay que **reabrir el corte** (como admin) y refrescar — al regenerarse se recalcula con la fórmula correcta. El reporte de técnicos carga en vivo, por lo que ese se ve corregido apenas se suban los archivos.

---

## Bug #3 — Transfers de stock fantasma que envenenaban el inventario

### Lo que vio el usuario

Un iPhone 13 ROJO (IMEI 350183982826737) vendido el 13/06 a Aaron Ramos seguía apareciendo como **disponible** en `/cellphone/13732/edit`. Y cuando intentaron corregir el inventario el 15/06, **tuvieron que hacer la corrección dos veces** porque la primera no agarró.

El mismo patrón se reprodujo con un Samsung S22 BLACK (IMEI 357452521054694) vendido el mismo día.

### Timeline reconstruido del iPhone 13

| Fecha | Evento en BD | Stock loc 6 |
|---|---|---|
| 13/06 **10:18** | **VENTA** factura 65770 (Aaron Ramos) | 1 → 0 ✓ |
| 13/06 **11:01** | **Transfer fantasma** loc 11 → loc 6 (creado por `ferperez`) | 0 → **1** ❌ |
| 15/06 06:37 | Corrección manual #1 | 1 → 0 |
| 15/06 06:41 | Corrección manual #2 (¿por qué la primera no agarró?) | 1 → 0 |

### Timeline del Samsung S22

| Fecha | Evento |
|---|---|
| 13/06 **11:26** | **VENTA** factura 65789 ($3,700) |
| 13/06 **11:40** | **Transfer fantasma** loc 11 → loc 6 — **14 minutos después de la venta** (creado por `ferperez`) |

Stock actual: loc 6 = **1** (el equipo aparece disponible pero ya no existe físicamente).

### La causa raíz

El usuario **Fernando Perez Serena** (`ferperez`) crea `purchase_transfer` desde Almacén Equipos (loc 11) hacia las sucursales aunque el almacén ya no tenga el equipo (porque se vendió antes o fue transferido).

**El sistema no validaba el stock de origen antes de aceptar el transfer.** Resultado: aparecía stock duplicado en sucursal destino — el equipo "resucita" como disponible aunque físicamente ya no exista.

Esto obligaba a hacer correcciones manuales que a veces "no agarraban" porque entre la primera corrección y la verificación visual podía dispararse otro proceso que restauraba el fantasma.

### Solución aplicada

Se agregó **validación de stock obligatoria** en `StockTransferController@store` antes de crear cualquier transacción. Para cada producto que se intente transferir:

1. Se consulta el stock actual en la **sucursal de origen** (`variation_location_details.qty_available`).
2. Si la cantidad disponible es menor a la cantidad pedida → **se rechaza el transfer**.
3. Se devuelve un error claro con el nombre del producto y la cantidad disponible vs solicitada.

Ejemplo de mensaje de error visible para el usuario:

> No se puede crear el transfer: la sucursal de origen no tiene suficiente stock.
> EQUIPO IPHONE 13 128GB ROJO: disponible 0, intentando transferir 1

### Test del fix

| Caso | Antes | Después |
|---|---|---|
| Transfer iPhone 13 desde Almacén (qty=0) | ✗ Permitido → fantasma | ✅ Bloqueado con mensaje claro |
| Transfer de producto con stock real | ✅ Permitido | ✅ Permitido (igual) |

### Archivos modificados

- `app/Http/Controllers/StockTransferController.php`

### Acción operativa pendiente

Hablar con **Fernando Perez Serena** para entender por qué crea esos transfers. El fix técnico previene el daño futuro, pero el flujo manual debe ajustarse:

- Si el equipo físicamente ya no está en el Almacén, **no hay que registrar transferencia** — el inventario ya descontó al momento de la venta en sucursal.
- Si el equipo físicamente sí estaba en sucursal y por error se vendió desde el Almacén, hay que **anular y rehacer la venta** desde la sucursal correcta, no crear un transfer compensatorio.

---

## Mejora #4 — Vista imprimible del corte diario con listado de ventas

A solicitud de la operación se agregó al show del corte (`/daily-cuts/{id}`):

- **Botón "Imprimir"** que activa el modo de impresión.
- **Listado completo de ventas del día** con: hora, número de factura (con link), cliente, vendedor, método de pago y monto.
- **Total al pie** que cuadra exactamente con el `total_sales` del corte.
- **Estilos de impresión** que ocultan menú, sidebar y botones, dejando solo el contenido para llevarse en papel.

Esto le permite a las cajeras tener **una hoja física con el resumen del día** que pueden usar para conciliar con su conteo manual sin tener que sumar a mano.

### Archivos modificados

- `app/Http/Controllers/DailyCutController.php` (método `show` ahora también consulta la lista de ventas)
- `resources/views/daily_cut/show.blade.php` (nueva sección + estilos de impresión)

---

## Resumen de archivos a subir a producción

```
app/Http/Controllers/TechnicianController.php
app/Http/Controllers/DailyCutController.php
app/Http/Controllers/StockTransferController.php
app/Utils/DailyCutUtil.php
resources/views/daily_cut/index.blade.php
resources/views/daily_cut/show.blade.php
```

**Sin cambios de schema de base de datos.**

## Pasos post-despliegue

1. **Limpiar caché de vistas** en producción borrando:
   ```
   storage/framework/views/*.php
   ```
2. **Reabrir y regenerar los cortes** del 13/06 en adelante (y de días anteriores donde se sospeche que hubo descuentos) para que el desglose por marca se recalcule con la fórmula correcta.
3. **Verificar manualmente**:
   - El reporte de técnicos del 13/06: las líneas de SERVICIO LIMPIEZA GENERAL que antes salían en $0 ahora muestran $100.
   - El corte 85: el banner de reconciliación muestra que las columnas cuadran con el total de ventas.
   - Intentar crear un transfer fantasma desde Almacén: debe ser rechazado con error claro.
4. **Hablar con Fernando Perez Serena** sobre el flujo de transfers desde Almacén.

---

## Lo que sabemos seguro vs lo que queda abierto

### Confirmado al 100%

- ✅ Los dólares **sí** se contabilizan correctamente en el corte.
- ✅ Los 13 "returns" del 13/06 eran **vueltos**, no devoluciones de producto (verificado con la fórmula `recibido − returned = final_total`).
- ✅ La fórmula de líneas con descuento estaba doble-restando.
- ✅ Los transfers fantasma fueron creados por el mismo usuario, sin nota, y reviven equipos vendidos.
- ✅ Las dos ventas (`/sells`) y el corte dan el mismo total de $48,649 para el 13/06 — el sistema es internamente consistente.

### Queda abierto

- El cajero contó $51,999 en ventas vs $48,649 del sistema. La diferencia de **$3,350** se explica parcialmente por:
  - $1,100 — diferencia de precios cuando hubo descuentos (cajera anota precio menu, sistema cobra el descontado).
  - $1,600 aproximadamente — categorización distinta (tapas traseras puestas en Reparaciones vs Accesorios).
  - El resto — errores de suma manual o de captura.
- El sistema es la fuente de verdad. La conciliación con la cajera requiere ajustar criterios manuales en sucursal, no código.
