# Diagnóstico — "El inventario no se descuenta después de una venta"

**Fecha:** 14 de mayo, 2026
**Sistema:** Celfix POS (pos.celfix.mx)

---

## Reporte original

> "En el sistema después de una venta no se descuentan los equipos del inventario...
> por ejemplo si tengo 3 iPhone 13 y vendo 1 no se está descontando del inventario,
> ¿hay algo en el código que cause esto o puede ser un error de cuando se importó la base de datos?"

---

## Conclusión

**El código sí descuenta el inventario correctamente en cada venta.** El problema viene de **datos inconsistentes desde la importación** de la base de datos del sistema anterior.

> ⚠️ **ACTUALIZACIÓN 21-may-2026:** El análisis original (Paso 4) sobreestimó el problema porque
> **no contaba las transferencias entre sucursales** (`purchase_transfer` / `sell_transfer`). Al
> reconciliar con los datos reales de producción incluyendo transferencias, el descuadre real es de
> apenas **~1%**, no masivo. Ver la sección **"Actualización con datos reales de producción"** al final,
> que reemplaza los números del Paso 4.

---

## Investigación

### Paso 1 — Verificar que el código descuenta

El flujo de venta llama a `productUtil->decreaseProductQuantity()` cuando el producto tiene `enable_stock = 1`:

```
SellPosController.php (línea ~548):
    if ($product['enable_stock']) {
        $this->productUtil->decreaseProductQuantity(
            $product['product_id'],
            $product['variation_id'],
            $input['location_id'],
            $decrease_qty
        );
    }
```

### Paso 2 — Verificar que los productos tienen `enable_stock = 1`

| Categoría | Total | enable_stock = 1 | enable_stock = 0 |
|-----------|-------|------------------|------------------|
| Todos los productos | 13,146 | 13,144 (99.98%) | 2 |
| Equipos (iPhones/Samsung) | 10,373 | 10,372 (99.99%) | 1 |

Prácticamente todos los productos tienen el stock habilitado. No hay un problema masivo aquí.

### Paso 3 — Probar una venta real

Última venta hecha en el sistema:
- Producto: LENS DE CRISTAL PARA CAMARA TRASERA IPHONE
- Comprado/Opening Stock: **50**
- Vendido históricamente: **1**
- Esperado: 50 − 1 = **49**
- Stock actual en el sistema: **49** ✓

El descuento de 1 unidad sí ocurrió en esa venta.

### Paso 4 — Auditar todas las variaciones

Ejecuté un audit cruzando `purchase_lines + opening_stock` vs `variation_location_details.qty_available`. Encontré **muchas variaciones donde `qty_available` no coincide con las transacciones que la respaldan**.

Ejemplos reales:

| Producto | Sucursal | Esperado por transacciones | En el sistema | Diferencia |
|----------|----------|---------------------------|----------------|------------|
| SILICONE CASE IPHONE 13 PRO | Loc 8 | 4 | 504 | **+500** |
| SILICONE CASE IPHONE 13 | Loc 8 | 0 | 100 | +100 |
| SILICONE CASE IPHONE 14 PRO MAX | Loc 8 | 4 | 24 | +20 |
| SILICONE CASE IPHONE 11 | Loc 8 | 0 | 10 | +10 |
| SILICONE CASE IPHONE 12 PRO MAX | Loc 6 | 11 | 21 | +10 |

Específicamente para SILICONE CASE IPHONE 13 PRO en Loc 8:
- Opening stock: **5 unidades** (única transacción registrada)
- Sold: **1 unidad**
- Cálculo esperado: 5 − 1 = 4
- **Stock real en el sistema: 504**
- ¿De dónde vienen esos 500 extras? **No hay ninguna transacción de compra, opening_stock, ajuste, ni transferencia que los respalde.**

---

## Causa raíz

Cuando se hizo la importación del sistema anterior:

1. Se cargó **directamente la columna `variation_location_details.qty_available`** con los stocks físicos.
2. **NO se crearon las transacciones de `opening_stock`** necesarias para respaldar esos valores.

Esto deja el inventario en un estado donde:
- El número `qty_available` existe pero **sin trazabilidad** (no se sabe de dónde vino).
- Los reportes de movimientos (compras, opening_stock, ventas) **no cuadran** con el stock actual.
- El cajero percibe que "no se descuenta" porque los números absolutos son grandes y poco confiables.

---

## Cómo se ve desde la perspectiva del cajero

- **Caso A:** El cajero tiene 3 iPhones físicos. En el sistema dice 503. Vende uno → el sistema lo baja a 502.
  - Aparentemente "no se descontó" porque sigue siendo un número muy grande comparado con lo físico (2 unidades).
  - Pero técnicamente sí se descontó 1.

- **Caso B:** El cajero tiene 3 iPhones físicos. En el sistema dice 0. Vende uno → el sistema lo intenta bajar a −1 (overselling permitido).
  - Aparentemente "no cambió nada" porque la pantalla sigue mostrando 0 o un negativo.
  - El descuento ocurrió, pero el punto de partida estaba mal.

---

## Soluciones recomendadas

### Opción 1 — Conteo físico + ajuste manual ⭐ Recomendada

Cada sucursal hace un conteo físico de su inventario y captura los valores reales vía la herramienta de **Stock Adjustments** del sistema.

- **Pros:** Datos 100% confiables después del proceso.
- **Contras:** Toma tiempo, requiere personal en cada sucursal.

### Opción 2 — Script de reconciliación automática

Un comando artisan que:

1. Toma el valor actual de cada `variation_location_details.qty_available`.
2. Calcula la diferencia entre eso y lo que dicen las transacciones (compras + opening_stock − ventas).
3. Crea una transacción de tipo `opening_stock` por la diferencia faltante, marcada como "Migración del sistema anterior".

Resultado: los movimientos quedan trazables y los reportes empiezan a cuadrar a partir de ese momento.

- **Pros:** Rápido. Mantiene los stocks actuales como base.
- **Contras:** Asume que los datos importados son correctos (cosa que aparentemente no se cumple por la perspectiva del usuario).

### Opción 3 — Resetear a "lo que dicen las transacciones"

Ajustar todos los `qty_available` para que coincidan con `(compras + opening_stock − ventas)`.

- **Pros:** Datos consistentes con las transacciones registradas.
- **Contras:** Muchos productos quedarían en 0 o negativos. Probablemente NO refleje la realidad física.

---

## Recomendación

**Combinar Opción 1 y Opción 2:**

1. **Corto plazo:** ejecutar el script de reconciliación (Opción 2) para que los reportes de movimientos empiecen a cuadrar.
2. **Mediano plazo:** programar conteos físicos por sucursal y hacer ajustes vía Stock Adjustments para tener números 100% confiables.

A partir de ese momento, cada venta y cada compra se reflejará correctamente en el inventario sin desfases.

---

## Anexo — Cómo verificar que el código sí descuenta

Cualquiera puede correr esta verificación con `php artisan tinker`:

```php
$tx = DB::table('transactions')
    ->where('type','sell')
    ->where('status','final')
    ->orderBy('id','desc')
    ->first();
$line = DB::table('transaction_sell_lines')->where('transaction_id', $tx->id)->first();

$purchased = DB::table('purchase_lines as pl')
    ->join('transactions as t','t.id','=','pl.transaction_id')
    ->where('pl.variation_id', $line->variation_id)
    ->where('t.location_id', $tx->location_id)
    ->whereIn('t.type', ['purchase','opening_stock'])
    ->sum('pl.quantity');
$sold = DB::table('transaction_sell_lines as tsl')
    ->join('transactions as t','t.id','=','tsl.transaction_id')
    ->where('tsl.variation_id', $line->variation_id)
    ->where('t.location_id', $tx->location_id)
    ->where('t.status', 'final')
    ->sum('tsl.quantity');
$actual = DB::table('variation_location_details')
    ->where('variation_id', $line->variation_id)
    ->where('location_id', $tx->location_id)
    ->value('qty_available');

echo "Esperado por transacciones: " . ($purchased - $sold) . "\n";
echo "Actual en el sistema: " . $actual . "\n";
```

Si los números coinciden → la venta sí descontó correctamente.
Si no coinciden → es la inconsistencia heredada de la importación.

---

# Actualización con datos reales de producción (21-may-2026)

Se importó el dump completo de producción (`pos_celfix_mx_2026-05-21`) a una base de datos
aislada (`celfix_prod_audit`) y se reconcilió **incluyendo transferencias entre sucursales**,
que el análisis original había omitido.

## Reconciliación correcta

Fórmula real del stock por (producto × sucursal):

```
qty_available_esperado = entradas − salidas

  entradas (purchase_lines):      opening_stock + purchase + purchase_transfer (transferencia recibida)
  salidas  (transaction_sell_lines): sell (final) + sell_transfer (transferencia enviada)
```

> El error del Paso 4 fue contar solo `opening_stock + purchase` e ignorar `purchase_transfer`.
> Por eso veía "504 vs 4" — esas ~500 unidades **sí entraron, pero por transferencia**.

## Resultado real (negocio CELFIX, business_id = 2)

| Métrica | Resultado |
|---|---|
| Filas producto × sucursal con stock habilitado | 43,342 |
| **Con descuadre real** | **449 (≈1%)** |
| Stock fantasma (sistema cree de más) | 429 filas, **+4,601** uds |
| Sobreventa (sistema cree de menos) | 20 filas, **−22** uds |

El código descuenta bien. El drift real es ~1%, concentrado en accesorios. Para **equipos antiguos**
(SKU `CF-` + 4–6 dígitos del IMEI): 3,652 filas, solo **69 descuadradas** (todas fantasma, ~1 c/u).

Equipos antiguos que el sistema cree EN STOCK (`qty_available > 0`):

| Sucursal | Equipos en stock (según sistema) |
|---|---|
| Sucursal Americas (6) | 28 |
| Sucursal Nuevo Mexicali (7) | 58 |
| Sucursal Villa Fontana (8) | 145 |
| Sucursal Benito Juárez (9) | 119 |
| Almacén Equipos (11) | 4 |
| **TOTAL** | **354** |

---

## Caso real demostrativo — LCD IPHONE 15 PRO MAX OLED (CF-LCDIP15PMO)

Reportes del usuario:
> "Agregué 2 y nomás me aparece que tengo 1."
> "Nuevo Mexicali tiene 1 en stock y aquí marca 0, pero en Stock de apertura marca 1, y no pueden vender porque marca 0."

### Rastreo en Sucursal Nuevo Mexicali (loc 7)

**Entrada:**
- 1 stock de apertura: **7 uds**, fechado **2026-04-08**, con `quantity_sold = 6`

**Salidas (7 en total):**

| Transacción | Tipo | Cant. | Fecha |
|---|---|---|---|
| tx 30359 | sell/final | 1 | **2025-09-01** ⚠️ |
| tx 100518 | sell/final | 1 | 2026-04-21 |
| tx 102289 | sell/final | 1 | 2026-04-27 |
| tx 103064 | **sell_transfer**/final | 1 | 2026-04-29 |
| tx 103692 | sell/final | 1 | 2026-05-02 |
| tx 103761 | sell/final | 1 | 2026-05-02 |
| tx 108788 | sell/final | 1 | 2026-05-18 |

7 entradas − 7 salidas = **qty_available = 0** (coincide con lo que muestra el sistema).

### Por qué "Stock de apertura marca 1" pero "Stock actual marca 0"

La pantalla de **Stock de apertura** calcula `cantidad − quantity_sold` del renglón de apertura.
El contador `quantity_sold` registra las **6 ventas** pero **NO la transferencia**. Entonces:

- Stock de apertura muestra: `7 − 6 = 1`
- Stock real (qty_available): `7 − 6 − 1 transferencia = 0`

**La pantalla de Stock de apertura miente: muestra de más, justo por la cantidad transferida.**

### Por qué "agregué 2 y aparece 1"

Mismo mecanismo: editar la apertura no limpia el `quantity_sold` heredado ni refleja transferencias,
así que el ajuste se "come" parte de lo que capturas.

### Anomalía que confirma que es la migración

Hay una venta fechada **2025-09-01**, siete meses **antes** de que se creara el stock de apertura
(**2026-04-08**). Eso es físicamente imposible: la migración importó las ventas viejas con su fecha
original, mientras que el stock de apertura se recreó en la fecha del import. El sistema termina
arrastrando ventas históricas duplicadas y contadores de apertura inconsistentes.

---

## Reglas prácticas para los gerentes / cajeros

🔴 **NO corregir inventario editando "Stock de apertura".** Ese campo:
- No refleja las transferencias entre sucursales (muestra de más).
- Interactúa con el `quantity_sold` heredado de la migración.
- Por eso "metés 2 y ves 1".

🟢 **El número confiable es "Inventario actual" / "Stock actual"** (`qty_available`) — es lo que el POS
usa para permitir vender.

🟢 **Para corregir, usar "Ajustes de inventario" (Stock Adjustment)**, no la apertura. El ajuste fija el
`qty_available` directo, con registro trazable, sin tocar ventas históricas ni depender del contador roto
de la apertura. Ejemplo: para el LCD en Nuevo Mexicali, ajuste de **+1** y ya pueden vender.

---

## Plan de acción acordado

1. **Backup de producción** (hecho — base `celfix_prod_audit` para análisis aislado).
2. **Relación de equipos** (los 354 que el sistema cree en stock) en Excel, una hoja por sucursal,
   con columna para que cada gerente marque "presente físicamente / vendido".
3. **Conteo físico** por sucursal sobre esa relación.
4. **Reconciliación masiva** vía Stock Adjustments (con trazabilidad) según el conteo.
5. **Prevención:** desactivar overselling, alertas de stock bajo, y bloquear SKUs antiguos para venta accidental.
