# Reporte de devoluciones

**Fecha:** 2026-07-02
**Alcance:** Análisis del flujo actual de devoluciones en producción y del bug reportado el 01/07/2026 con el cliente Daniel Brambila.
**Estado:** Investigación completada, pendiente confirmación operativa antes de aplicar arreglos.

---

## El caso concreto que se reportó

El 01/07/2026 la cajera de Sucursal Americas recibió una devolución y reportó por WhatsApp dos preguntas:

> "El dinero que vamos a regresar en donde se descuenta? Tengo esta que se hizo devolución de $4500 pesos, se le regresó $3,350 pesos ya que el resto lo uso para una reparación pero esos $3,350 pesos que saqué hoy de la caja donde se refleja?"

> "El saldo de lo que le regresamos me sale ahí como que debe el cliente dinero. Como lo soluciono? Ósea nosotros le debemos a él? Ahí como lo manejaron. Con el saldo negativo no la deja sacar la venta."

## Los datos exactos en la base

Cliente: **Daniel Brambila** (contact_id 36884)

| Fecha | Evento | Estado |
|---|---|---|
| 22/06/2026 11:39 | Venta original — iPhone 13 128GB BLUE OPORTUNIDAD (IMEI 351224674833034) por **$4,500** | Pagada con Tarjeta Banorte |
| 01/07/2026 10:36 | Devolución del equipo — Nota de crédito CN2026/0003 por **$3,350** | ⚠️ payment_status = "due" (pendiente) |
| 01/07/2026 17:21 | Nueva reparación al mismo cliente — Factura 70082 | ⚠️ final_total = $0 |

## Cómo se muestra en la pantalla del cliente

En `https://pos.celfix.mx/contacts/36884` aparece la etiqueta:

> **Cliente debido: −$3,350.00**

Ese número negativo con la etiqueta "Cliente debido" es lo que confunde a la cajera. En realidad significa: "hay un saldo a favor del cliente de $3,350 pendiente de reembolsar".

---

## Los 4 puntos que encontré rotos en el sistema

### Punto 1 — El formulario de devolución no tiene sección para registrar el reembolso

Cuando la cajera abre el formulario de nueva devolución en el sistema, solo puede rellenar:

- Qué productos regresan y en qué cantidad
- Un descuento (opcional)
- Botón "Guardar"

**No hay ningún campo para indicar "¿Cómo le pagué de vuelta al cliente? ¿Cash del cajón? ¿Reembolso a la tarjeta?"** El sistema crea la devolución como "pendiente de reembolsar" y ya. Nunca queda registrado que el efectivo salió de la caja.

### Punto 2 — El paso "Agregar pago" existe pero está escondido

Sí existe una opción para cerrar la devolución (registrar cómo se le pagó al cliente): está en el listado `/sell-return`, dentro del menú desplegable "Acciones" de cada devolución, opción "Agregar pago".

Pero después de crear una devolución, el sistema **no lleva ahí automáticamente** ni muestra ningún aviso de que "todavía falta registrar el reembolso". La cajera queda pensando que ya terminó.

**Evidencia en producción:** solo **1 de 3** devoluciones históricas está marcada como "paid". Las otras 2 están colgadas como "due", acumulando un supuesto crédito a favor de clientes por **$16,100** que en realidad ya se les entregó físicamente en efectivo.

### Punto 3 — Los reembolsos no aparecen en el corte diario

El corte diario (`/daily-cuts`) solo cuenta transacciones de tipo "sell". Ignora las de tipo "sell_return".

Esto significa que **aunque la cajera hiciera bien todo el flujo** (crear devolución + agregar pago cash), el corte del día seguiría mostrando el mismo total de efectivo. En la realidad la caja bajó $3,350 pero el corte dice lo mismo que antes.

Esto explica la pregunta original: "esos $3,350 que saqué hoy de la caja, dónde se reflejan?" — **la respuesta es: en ningún lado, es un bug del código del corte.**

### Punto 4 — La cajera improvisó un workaround que empeora las cosas

Como el sistema no da opción de registrar cómo se paga la devolución, la cajera hizo lo siguiente:

En vez de registrar la devolución por $4,500 (valor real del iPhone) y luego reembolsar $3,350, decidió:

- Editó el precio del formulario para que la devolución quedara en $3,350 (solo el efectivo entregado)
- No registró ningún pago

Esto rompe la contabilidad porque:

- El inventario dice que regresó 1 iPhone ✓
- Pero el valor contable de la devolución dice $3,350 en vez de $4,500 ✗
- Al crear la nueva reparación del mismo cliente, el sistema intentó auto-compensar el "crédito" pendiente y por eso la reparación quedó en $0.00
- Al final la cajera no puede cobrar la reparación normal porque hay saldos cruzados

---

## Cómo debería funcionar el proceso correcto (según UltimatePOS)

El flujo correcto para el caso del iPhone sería:

1. **Devolución**: valor completo del equipo → $4,500. El iPhone regresa al inventario.
2. **Registrar el reembolso — pago 1**: $3,350 en efectivo (salida física del cajón). La devolución queda como "parcialmente pagada" con $1,150 pendientes de reembolsar.
3. **Nueva venta reparación**: por el precio real (por ejemplo $1,150). Se paga con el crédito pendiente que quedó del punto 2. Se cierra todo.

Al final del día:

- Cliente recibió: 1 iPhone regresado + $3,350 cash + reparación por $1,150
- Nosotros recibimos: $4,500 originalmente por tarjeta
- Saldo del cliente: $0 (todo cuadra)
- Efectivo salió del cajón: $3,350 (debería reflejarse en el corte)

---

## Preguntas para confirmar antes de arreglar

Necesito respuestas operativas antes de tocar código:

### Pregunta A — Métodos de reembolso disponibles en sucursal

Cuando se hace una devolución, ¿qué opciones tiene la cajera para pagarle al cliente?

- [ ] Efectivo del cajón
- [ ] Reembolso a la misma tarjeta que se usó en la venta original
- [ ] Vale / crédito para uso futuro en la sucursal
- [ ] Transferencia bancaria
- [ ] Otro (especificar)

Necesito saber cuáles activo como opciones en el formulario.

### Pregunta B — Reembolsos parciales con crédito pendiente

En el caso de Daniel Brambila, se le dieron $3,350 en efectivo y el resto ($1,150) se aplicó a una reparación.

- ¿Este patrón (parte cash, parte crédito para siguiente venta) es común o excepcional?
- Si el cliente tiene $1,150 de crédito pero al final ya no va a comprar nada, ¿la sucursal se lo entrega en efectivo en otra visita? ¿O expira? ¿Cómo se maneja?

### Pregunta C — ¿Puede la cajera editar el precio de la devolución?

Actualmente el formulario permite cambiar el precio unitario de un producto devuelto. Esto abre la puerta a errores como el que vimos.

- ¿Hay alguna razón legítima para que la cajera pueda cambiar el precio del item que está regresando? (Ejemplo: el precio original fue una promo que ya no aplica, se devuelve al precio actual)
- O deberíamos **bloquear** que se pueda editar y forzar que siempre sea el precio de la venta original?

### Pregunta D — Cortes diarios pasados con devoluciones no reflejadas

Cuando arregle que el corte diario cuente las devoluciones cash como salida, aplicará a **cortes nuevos**. Los cortes ya cerrados no cambian automáticamente.

- ¿Hay que **regenerar** los cortes de días anteriores para corregir históricamente? (Ejemplo: el corte del 01/07 Sucursal Americas actualmente no refleja los $3,350 que salieron)
- ¿O es aceptable que solo cortes futuros sean correctos y los pasados quedan como están?

### Pregunta E — Autorización para devoluciones

Actualmente cualquier cajero con permiso de "sell.access" puede crear una devolución.

- ¿Debería requerirse autorización de admin/gerente para hacer una devolución mayor a X pesos? (Ejemplo: mayor a $1,000)
- Esto no arregla nada del bug técnico, pero previene fraudes o errores caros.

### Pregunta F — Reembolsos a tarjeta

Si se activa "Reembolso a tarjeta" como método:

- ¿La cajera hace el reembolso físicamente en la terminal aparte y solo REGISTRA en el sistema que ya lo hizo?
- O deberíamos integrar con la terminal? (Probablemente no factible con Banorte/Banbajio sin API)

---

## Qué se arreglaría con base en las respuestas

Dependiendo de las respuestas, los arreglos técnicos serían:

### Arreglo 1 — Sección de reembolso en el formulario de devolución

Al crear una devolución, la cajera vería DEBAJO del listado de productos:

```
Total de devolución: $4,500

¿Cómo se le paga al cliente?
┌─────────────────────────────────────────────┐
│ [Método: Efectivo ▼]  [Monto: 3350.00     ] │
│ (+ agregar otra fila)                        │
└─────────────────────────────────────────────┘

Restante: $1,150 (quedará como crédito del cliente)

[Guardar y cerrar devolución]
```

Todo en una sola pantalla, sin pasos ocultos.

### Arreglo 2 — Corte diario refleja reembolsos cash

Modificar `DailyCutUtil::compute()` para que también sume los pagos cash de `sell_return` del día como salidas negativas de `total_cash`.

### Arreglo 3 — Vista de contacto más clara

Cambiar la etiqueta confusa "Cliente debido: −$3,350" por una explicación explícita:

```
⚠️ Devolución pendiente de reembolsar: $3,350
[Ver devolución CN2026/0003]
```

### Arreglo 4 — Bloquear edición de precio en devolución (si Pregunta C dice bloquear)

En el formulario de devolución, el precio unitario aparece como solo lectura. Si necesitan aplicar un descuento en la devolución hay que usar el campo dedicado de descuento, no editar el precio.

### Arreglo 5 — Autorización si Pregunta E dice sí

Agregar validación en `SellReturnController@store`: si el monto es mayor a X y el usuario no tiene permiso `sell_return.approve_high`, rechaza y pide firma de admin.

---

## Impacto de no arreglar

Si dejamos el sistema como está:

- **Crédito falso acumulado a clientes**: hoy son $16,100. Va a seguir creciendo.
- **Cortes diarios inflados**: cada devolución cash que ocurre hace que el corte diga $X más de lo que realmente hay en la caja.
- **Bloqueos operativos recurrentes** como el de Daniel Brambila: cliente con "saldo negativo" que no deja hacer venta nueva.
- **Riesgo de fraude interno**: como no queda registrado quién sacó el efectivo del cajón por devolución, un empleado puede crear una devolución falsa y llevarse el efectivo. El sistema no lo detecta.
- **Auditoría contable inconsistente**: si un contador revisa las cuentas por cobrar/pagar del negocio, va a ver estos "saldos a favor de cliente" que en realidad ya se pagaron.

---

## Siguientes pasos

1. **Confirmar** las respuestas a las 6 preguntas de arriba
2. **Definir** cuáles arreglos se aplican y en qué orden
3. **Decidir** qué hacer con los 2 casos históricos abiertos y con este caso de Daniel Brambila (limpiar manualmente o dejar así)
4. **Aplicar** los arreglos técnicos según lo confirmado

Con las respuestas puedo entregar los arreglos en 1 o 2 días.
