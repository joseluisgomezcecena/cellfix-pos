# Reporte del caso factura 72650 — venta duplicada del 13/07/2026

**Fecha del reporte:** 2026-07-14
**Fecha del evento:** 2026-07-13
**Sucursal:** Sucursal Americas
**Cajero involucrado:** Manuel Castillo (usuario `josecastillo`, id=38)

---

## Lo que se reportó

- Se sacaron 2 iPhones a "diferente hora" pero ambos tickets salieron con la **misma factura (72650)**, misma fecha y misma hora (09:24 AM)
- En el corte del día, la venta del iPhone entregado a **Deyssy Bojorquez** no aparecía
- Sobraban aproximadamente **$24,000 en efectivo** en el cajón vs lo que decía el sistema
- Se tuvo que hacer otra nota nueva para Deyssy más tarde en el día

---

## Qué pasó realmente

El sistema **sí registró la venta original de Deyssy**, pero después **el mismo cajero la editó 3 veces** hasta convertirla en la venta de Manuel Alamilla. Este es el timeline verificado a partir del registro de auditoría interno del sistema (`activity_log`):

| Momento aprox | Acción | Cliente | Producto | Monto |
|---|---|---|---|---|
| **09:24** | Se **crea** la venta | Deyssy Bojorquez | iPhone 17 Pro Max 256GB **BLUE** | **$24,500** |
| **~1h después** | Se **edita** la venta | (mismo registro) | (se cambia) | $300 |
| **~5h50min después** | Se **edita** la venta otra vez | Manuel Alamilla | iPhone 17 Pro Max 256GB **DEEP BLUE** | **$22,500** |
| **~1min después** | Se **edita** una vez más (trivial) | (mismo) | (mismo) | $22,500 |

Los 4 cambios los hizo el mismo usuario: **`josecastillo` (Manuel Castillo)**.

Es decir, la transacción con factura 72650 nació siendo la venta de Deyssy, pero fue **reutilizada** (editando cliente, producto, monto y método de pago) para meter ahí la venta de Manuel Alamilla horas más tarde.

---

## Por qué el efectivo cuadraba fuera del sistema

Las cuentas explican perfectamente el faltante en el corte:

| Concepto | Monto |
|---|---|
| Deyssy pagó en efectivo por su iPhone BLUE | **$24,500** |
| Manuel Alamilla pagó en efectivo por su iPhone DEEP BLUE | **$22,500** |
| **Total físico en el cajón al final del día** | **$47,000** |
| Sistema registró (solo la última edición, que es la venta de Manuel) | $22,500 |
| **Diferencia física − sistema** | **$24,500 ≈ los $24,000 "que sobraban"** |

El dinero de Deyssy nunca desapareció — estaba físicamente ahí. Lo que pasó es que su venta original fue **sobreescrita** con la de Manuel Alamilla.

---

## Por qué el iPhone BLUE parecía "vendible" a las 19:02

Cuando la venta original de Deyssy fue editada y se le quitó el iPhone BLUE, el sistema **regresó el iPhone al inventario** (qty=1) — como si nunca se hubiera vendido, porque efectivamente el registro de esa venta ya no existía.

Físicamente Deyssy ya se lo había llevado en la mañana, pero para el sistema el equipo seguía disponible.

A las 19:02 alguien hizo la **nota nueva** para Deyssy (factura 72965 por $24,310) y ahí se descontó por fin el iPhone BLUE del inventario. Pero durante 10 horas ese equipo estuvo "fantasma" en el sistema — cualquiera pudo haberlo vendido de nuevo.

---

## Dónde está el problema exactamente

**No es que el sistema fallara al guardar la venta de Deyssy** (esa era la sospecha inicial de todos, y la más lógica). El sistema sí la guardó.

**El problema es que el sistema permite editar una venta ya finalizada de manera tan libre**, que se puede:

1. Cambiar el cliente
2. Cambiar el producto
3. Cambiar el monto
4. Cambiar el método de pago

...sin generar una nueva factura, sin dejar un aviso visible, y **sin restringir esa acción a un administrador**. Cualquier cajero regular puede entrar a una venta ya guardada y "sobreescribirla".

En este caso Manuel Castillo hizo esto **posiblemente sin darse cuenta de las consecuencias**, o pensando que "editar la venta anterior" era una forma válida de meter la nueva venta. Pero el efecto es que la venta original **se borra silenciosamente**.

Este mismo mecanismo, sin las restricciones apropiadas, es una puerta abierta para:

- Errores de captura como el que acaba de pasar (dinero "sobrante" físico sin registro)
- Fraudes intencionales (registrar venta grande, cobrar, luego editar a monto menor y quedarse con la diferencia)

---

## Lo que se está arreglando en el sistema

Se van a agregar las siguientes restricciones para prevenir que esto vuelva a pasar:

### Restricción 1 — Solo administradores pueden editar ventas guardadas

Cajeros regulares ya no van a poder entrar a la edición de una venta que ya se cerró. Si se cometió un error, tendrán que pedirle al gerente/admin que la revise.

### Restricción 2 — No se puede cambiar cliente ni productos de una venta

Si es realmente el cliente equivocado o el producto equivocado, hay que **anular la venta y hacer una nueva**. La edición solo permitirá corregir cosas menores (nota, descuento pequeño, método de pago).

### Restricción 3 — Cambios grandes requieren justificación registrada

Si se edita el monto de una venta y cambia más del 20%, se pedirá una razón que quedará guardada visiblemente en el registro de la venta.

### Restricción 4 — Auditoría automática

Se va a revisar el histórico para ver si hay más casos como este que no habían sido detectados. Con eso podemos entender el tamaño real del problema y ajustar procesos.

---

## Recomendaciones operativas inmediatas (mientras se aplican los fixes)

Estas cosas se pueden hacer **hoy** sin esperar a los cambios del sistema:

### Para las cajeras/os

1. **NUNCA editar una venta ya guardada para meter otra**. Si el cliente cambia o el producto cambia, hay que crear una nueva venta desde cero.
2. **Si te equivocaste** al capturar una venta, avisar al gerente para que la anule oficialmente antes de crear la corregida.
3. **Cada venta debe tener su propia factura**. Si el ticket ya salió y no cuadra, no editar — anular y rehacer.

### Para gerentes/admins

1. **Revisar el corte todos los días comparando con conteo físico** — si sobra o falta dinero significativo, investigar antes de cerrar.
2. **Cuando aparezca un desbalance de este tipo**, no asumir que "el sistema falló". Revisar el historial de ediciones (audit trail) de las ventas del día antes de descartar causas humanas.
3. **Considerar reglas internas de segregación de deberes**: quien hace ventas no debería poder editarlas después. Esto es principio contable estándar.

---

## Sobre la disciplina interna

Este reporte se enfoca en los hechos técnicos verificados en la base de datos.

**No podemos determinar desde el sistema si esto fue un error honesto de captura** (Manuel Castillo confundió "editar" con "crear nueva") **o si fue algo intencional**. Eso lo tiene que evaluar la administración de Celfix con base en el historial del empleado, el contexto del día, y una plática directa.

Lo que sí se puede decir:

- Los datos del `activity_log` son claros y no admiten interpretación distinta: la misma cuenta de usuario hizo los 4 cambios.
- Los tiempos entre ediciones (1h y 5h50min) no encajan con un "click accidental" — son ediciones deliberadas separadas en el tiempo.
- La secuencia de montos ($24,500 → $300 → $22,500) es sospechosa porque incluye un valor intermedio ($300) sin explicación operativa clara.

Es totalmente posible que sea un error honesto de aprendizaje operativo — se recomienda hablar con Manuel Castillo antes de asumir mala intención. Pero **el fix del sistema es obligatorio de todos modos**, porque este mismo hueco lo puede usar cualquier otra persona por accidente o por diseño.

---

## Resumen para presentación

- **El sistema no falló en guardar la venta.** La venta de Deyssy sí se guardó, después alguien la editó y la sobreescribió.
- **El dinero "sobrante" en el corte estaba ahí** porque se cobró Deyssy y esa venta desapareció al ser editada.
- **El iPhone BLUE "fantasma"** ocurrió porque al editar la venta se le quitó ese producto y el sistema lo regresó al inventario, cuando físicamente ya se había ido.
- **La misma cuenta de usuario hizo los 4 cambios** — es un evento humano, no un fallo espontáneo del software.
- **El sistema sí tiene un problema**: permite este tipo de edición sin las restricciones necesarias, y eso se va a corregir.

El objetivo del fix no es "castigar" nada — es **prevenir que este mismo error de proceso pueda volver a pasar**, ya sea por confusión o por mala intención.
