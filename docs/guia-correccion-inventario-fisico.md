# Guía: Corrección de inventario con conteo físico

**Para:** equipo de sucursales y administración
**Cuándo usar:** una vez por sucursal, para alinear el inventario del sistema con la realidad física
**Tiempo estimado:** medio día por sucursal (depende del volumen)

---

## ¿Por qué hacemos esto?

El inventario en el sistema **no coincide con la realidad física** porque viene mal desde la migración del sistema anterior — no es un error de las ventas actuales. La única manera correcta de dejarlo igual es:

1. **Contar físicamente** lo que hay en cada sucursal.
2. **Comparar contra el sistema** lo que dice "Inventario actual".
3. **Corregir las diferencias** usando la herramienta `/stock-corrections` (Corrección de inventario).

> ⚠️ **No usar** "Stock de apertura" ni "Ajuste de inventario" estándar — esos no resuelven el problema y pueden romper ventas futuras.

---

## Antes de empezar

### Requisitos
- Acceso al POS con permisos de administrador (rol que pueda entrar a `/stock-corrections`).
- Una hoja impresa o tablet con el inventario actual del sistema (lo bajan de Reportes → Stock).
- Lápiz, marcador para tachar lo ya contado.
- **No tener ventas activas durante el conteo** (idealmente fuera de horario o con un cartel de "Inventariando").

### Antes del conteo: imprimir el "antes"
1. Entrar a **Reportes → Reporte de Stock** (`/reports/stock-report`).
2. Filtrar por **la sucursal** que vas a contar.
3. Exportar a Excel o PDF y guardarlo. Este es tu **estado inicial** (sirve como referencia y respaldo).

---

## Paso a paso (una sucursal a la vez)

### Paso 1 — Contar físicamente

Cuenta TODO lo que hay físicamente en la sucursal, producto por producto:
- Equipos (uno por uno, registrando el IMEI/SKU).
- Accesorios (por modelo).
- Reparaciones pendientes (también son inventario).
- Hidrogel, VT, etc.

Anota la cantidad real por cada producto en tu hoja.

### Paso 2 — Comparar con el sistema

Para cada producto que contaste:
1. Mira la columna **"Inventario actual"** (qty_available) de la sucursal.
   > ⚠️ NO mires "Stock de apertura" — esa columna está corrupta por la migración y siempre se ve mal.
2. Compara con tu conteo físico.
3. Marca el resultado:
   - ✅ Coincide → no hacer nada.
   - 🔼 **Hay más físico que en sistema** → hace falta hacer una **Entrada**.
   - 🔽 **Hay menos físico que en sistema** → hace falta hacer una **Salida**.

### Paso 3 — Aplicar la corrección en el POS

Ir a **Menú lateral → Ajuste de stock → Corrección de Inventario** (`/stock-corrections`) y dar clic en **+ Nueva corrección**.

#### A) Si hay MÁS físico que en sistema (Entrada)

1. Selecciona la **sucursal**.
2. Busca el producto (por nombre, SKU o IMEI).
3. Selecciona **Tipo: Entrada (Sumar)**.
4. Cantidad: la diferencia que falta agregar (no el total — solo lo que falta).
   - Ejemplo: sistema dice 3, físico hay 5 → cantidad de Entrada = **2**.
5. **Motivo:** elegir el más adecuado (ej. "Encontrado en inventario físico", "Auditoría").
6. **Nota** (opcional pero recomendado): número de inventario, fecha, quién contó.
7. Guardar.

#### B) Si hay MENOS físico que en sistema (Salida)

1. Selecciona la **sucursal**.
2. Busca el producto.
3. Selecciona **Tipo: Salida (Restar)**.
4. Cantidad: la diferencia que sobra en sistema.
   - Ejemplo: sistema dice 7, físico hay 5 → cantidad de Salida = **2**.
5. **Motivo:** ej. "Merma", "No encontrado en auditoría", "Faltante histórico (migración)".
6. **Nota:** explicar brevemente.
7. Guardar.

> ✅ Después de guardar, el sistema **muestra automáticamente** la cantidad antes y después. Verifica que la cantidad después coincida con tu conteo físico.

### Paso 4 — Validar

Al terminar la sucursal:
1. Regresa al **Reporte de Stock** y filtra por esa sucursal.
2. Compara contra tu hoja de conteo físico.
3. Si hay diferencias residuales (no debe haberlas), repite el Paso 3 hasta que cuadre.

---

## Casos especiales

### 1. Equipos con SKU "CF-…" (equipos migrados)

Hay aproximadamente **354 equipos** con SKU tipo `CF-1234` que se importaron del sistema viejo. Son los más problemáticos. **Cada CF- es un equipo único** (un IMEI, una pieza).

- **Aparece en sistema, está físicamente** → ✅ déjalo.
- **Aparece en sistema, NO está físicamente** → Salida con motivo "No encontrado en auditoría". Estos son los típicos casos de equipos vendidos sin descontar antes.
- **NO aparece en sistema con su IMEI, pero está físicamente** →
  - **NO** hagas una Entrada usando otro CF- "parecido". Cada CF- es único.
  - Lo correcto es **crear el producto nuevo** (con su IMEI real) y luego hacerle la Entrada inicial.
  - Si no estás seguro, déjalo apartado y consulta con administración antes de moverlo.

### 2. Equipos en apartados activos

**Antes de hacer una Salida** de cualquier producto (especialmente equipos), revisa que **no esté reservado en un apartado activo**:

1. Ve a **Menú lateral → Apartado → Equipos Apartados** (`/equipos-apartados`).
2. Si el producto aparece ahí, **NO lo bajes del inventario** — está separado para un cliente que ya pagó anticipo.

### 3. Reparaciones pendientes

Las reparaciones pendientes (`repair_status = 'pending'`) también son inventario físico (el equipo del cliente está ahí). No las cuentes como producto en stock — son del cliente, no de la sucursal.

### 4. Productos en tránsito (transferencias)

Si hay productos en una transferencia entre sucursales que aún no se ha recibido, no los cuentes como inventario de la sucursal hasta que se reciban formalmente.

---

## Motivos que pueden usar

| Caso | Tipo | Motivo recomendado |
|---|---|---|
| Apareció físicamente algo que no estaba en sistema | Entrada | Encontrado en inventario físico |
| Auditoría rutinaria — sobrante | Entrada | Auditoría |
| Producto que se vendió pero no se descontó | Salida | Faltante histórico (migración) |
| Mercancía dañada / extraviada | Salida | Merma |
| No se encontró en el conteo | Salida | No encontrado en auditoría |

---

## Después del conteo

1. **Guarda el reporte "antes"** (el que imprimiste al inicio) junto con tu hoja de conteo físico y firma de quien contó. Sirve de respaldo.
2. **Cada corrección queda registrada** en `/stock-corrections` con fecha, usuario, cantidad antes, cantidad después y motivo. Es auditable.
3. **De aquí en adelante** todas las ventas descontarán correctamente. El sistema ya está alineado.

> ✅ Si después de esto vuelven a ver diferencias, **ya no es de la migración** — sería un problema operativo del día a día (ventas no registradas, mercancía no recibida en compras, etc.) y se investiga distinto.

---

## Preguntas frecuentes

**¿Tengo que cerrar la tienda para hacer esto?**
No es obligatorio, pero sí recomendable. Si vas a contar mientras hay ventas, las ventas que ocurran van a cambiar el inventario y te van a desfasar el conteo. Lo más fácil es hacerlo antes de abrir o después de cerrar.

**¿Qué pasa si me equivoco en una corrección?**
Puedes hacer otra corrección al revés (Entrada si te pasaste, Salida si te quedaste corto). Ambas quedan registradas — el historial no se borra, pero el saldo queda corregido.

**¿Por qué no usar "Stock de apertura"?**
Porque esa pantalla **no actualiza el contador de ventas** correctamente cuando hay transferencias entre sucursales, y arrastra los valores rotos de la migración. La diferencia técnica: "Stock de apertura" no crea el respaldo de compra que el POS necesita para vender después, así que aunque "veas" el stock subir, al intentar vender te va a dar el error "desajuste compra/venta". `/stock-corrections` sí crea ese respaldo automáticamente.

**¿Cada cuánto debo hacer esto?**
Una vez como ejercicio de saneamiento (después de la migración). Después, conteos físicos rutinarios cada 3-6 meses como buena práctica de cualquier negocio.

---

**Cualquier duda durante el proceso → escribir a administración antes de hacer una corrección de la que no estén seguros.** Es mucho más fácil consultar antes que deshacer después.
