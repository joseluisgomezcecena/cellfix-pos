# Guía operativa — Cortes diarios

**Para:** cajeros, encargados de sucursal y administradores
**Tiempo de lectura:** 5 minutos
**Cuándo consultar:** la primera vez que usas el sistema o cuando aparezca alguno de los casos especiales.

---

## TL;DR — Resumen ultra-rápido

| Caso | Qué hacer |
|---|---|
| Sucursal cierra a las 6 PM (normal) | **Nada.** El sistema lo hace solo. |
| Sucursal cierra antes (ej. sábados 3 PM) | Presionar **"Cerrar caja"** en tu fila al terminar. |
| Llegó una venta tarde, después de cerrar | Presionar **"Reabrir"** + **"Cerrar caja"** otra vez. |
| Te equivocaste y cerraste antes de tiempo | Presionar **"Reabrir"** (solo admin). |

---

## 1. Cómo funciona el corte diario

Cada sucursal tiene **un corte por día**. El corte es una "foto" de las ventas, efectivo, tarjetas, etc., de esa sucursal hasta el momento en que se cierra.

Hay **2 maneras** de cerrar un corte:

### A) Automático — a las 18:00 hrs
Para las sucursales que cierran a las 6 PM. El sistema:
- Genera el corte con todas las ventas hasta ese momento.
- Lo marca como **CERRADO** automáticamente.
- A partir de ahí, nadie puede modificarlo (a menos que un admin lo reabra).

**¿Quién lo dispara?** El primer usuario que entre al sistema después de las 18:00. Incluso si nadie entra inmediatamente, en cuanto alguien lo haga (por ejemplo, al revisar reportes a las 18:30), el corte se genera de un solo golpe.

### B) Manual — botón "Cerrar caja"
Para sucursales que cierran antes de las 18:00 (ej. sábados a las 15:00) o cuando quieres congelar el corte en un momento específico.

**Cómo hacerlo:**
1. Ir a **Menú lateral → Cortes Diarios** (`/daily-cuts`).
2. Encontrar la fila de tu sucursal del día de hoy. Tendrá un badge verde **"EN CURSO"**.
3. Presionar el botón naranja **"Cerrar caja"** en la columna **Acción**.
4. Confirmar el diálogo. El badge cambia a gris **"CERRADO"** con la hora exacta del cierre.

Después de cerrar manualmente, **el heartbeat de las 18:00 IGNORA esa sucursal** — no la sobreescribe.

---

## 2. Estados visibles

En la lista de cortes (`/daily-cuts`) cada fila muestra su estado:

| Badge | Significa |
|---|---|
| 🟢 **EN CURSO** (verde) | El corte se sigue actualizando con cada acceso al reporte. Las ventas nuevas se suman. |
| ⚫ **CERRADO** (gris) | Foto congelada. Nadie la modifica. Pasa el mouse sobre el badge para ver la hora exacta. |

---

## 3. Casos especiales

### 3.1 Una venta legítima llegó DESPUÉS del cierre

**Ejemplo**: Sucursal cerró a las 15:00 (sábado). Cliente llegó 14:58, el cajero terminó la transacción a las 17:00 (registró cualquier cosa o tuvo que terminar después).

**Síntoma**: La venta aparece en `/sells` (Todas las ventas) con la fecha correcta. Pero **NO aparece en el corte de hoy** (porque el corte ya está congelado).

**Solución** (1 minuto):
1. Ir a `/daily-cuts`.
2. Encontrar tu sucursal del día de hoy → ya tiene badge **"CERRADO"**.
3. Presionar **"Reabrir"** (solo admin). El badge vuelve a **"EN CURSO"**.
4. Presionar **"Cerrar caja"** otra vez → la venta nueva ya queda incluida en el corte.
5. Vuelve a imprimir el corte si ya habías hecho la conciliación física — los números cambiaron.

> ⚠️ **No olvides** que el efectivo de esa venta tardía debe ir a la caja registradora. Si la sucursal ya cerró físicamente, el cajero del día siguiente tendría que tomar nota de ese dinero.

### 3.2 Cerraste por error antes de tiempo

**Ejemplo**: A las 14:00 presionaste "Cerrar caja" pensando que era la hora correcta, pero todavía vas a vender.

**Solución** (admin):
1. Ir a `/daily-cuts`.
2. Encontrar el corte cerrado por error.
3. Presionar **"Reabrir"** → vuelve a estar mutable.
4. Sigue trabajando normal. Al final del día (manual o auto a las 18:00) se cierra correctamente.

### 3.3 Tu sucursal cierra a horarios variables

**Recomendación**: tener una regla simple:
- "Al cerrar la puerta de la sucursal, contar efectivo, presionar Cerrar caja, imprimir y firmar."
- Si después de eso llega una venta tardía, aplica la rutina de [3.1](#31-una-venta-legítima-llegó-después-del-cierre).

### 3.4 Apartados (layaways) — ¿cómo aparecen en el corte?

**Regla clave**: el dinero del apartado **NO entra al corte hasta que el equipo se entrega completamente pagado**.

| Momento | ¿Aparece en el corte? |
|---|---|
| Cliente paga enganche (apartado activo) | ❌ NO. El dinero físicamente vive en la caja del equipo, no en la caja registradora. |
| Cliente abona algo más (apartado activo) | ❌ NO. Mismo motivo. |
| Cliente paga el último abono y se lleva el equipo (apartado **completado**) | ✅ SÍ. Toda la suma (enganche + abonos + pago final) aparece consolidada en el corte del día de entrega. |

**Esto requiere que el sistema sepa el día de la entrega**. Lo detecta automáticamente cuando el `balance_due` llega a 0 y el status pasa a `completed`. Si por alguna razón un apartado quedó marcado como completado pero no aparece en el corte, contactar a soporte.

---

## 4. Botones del menú `/daily-cuts`

| Botón | Para qué sirve |
|---|---|
| **Vista semanal** | Resumen Sab→Vie con totales por día. |
| **Reporte de denominaciones** | Desglose de efectivo (billetes y monedas) por día. |
| **Excel semanal (por sucursal)** | Descarga un libro Excel con un sheet por sucursal de la semana actual. |
| **Excel detallado** | Descarga un Excel con cada corte línea por línea para un rango de fechas. |
| **Generar ahora** | Fuerza la generación del corte de hoy en ese momento. Útil si quieres ver el conteo en curso. **No congela**, solo genera. |
| **Regenerar histórico** | (Solo admin, solo después de un fix importante). Vuelve a calcular todos los cortes históricos con la lógica actual. |
| **Cerrar caja** (por fila) | Congela el corte de esa sucursal/día. El heartbeat de 18:00 la ignorará. |
| **Reabrir** (por fila, admin) | Quita el congelamiento. El corte vuelve a actualizarse. |

---

## 5. Preguntas frecuentes

**P: Si nadie entra al sistema entre las 18:00 y la medianoche, ¿se pierde el corte automático?**
R: No. El cron de aaPanel (si está configurado) lo dispara puntual a las 18:00. Si tampoco hay cron, el primer usuario que entre al día siguiente lo genera retroactivamente.

**P: ¿Puedo cerrar el corte ANTES de las 18:00 y todavía no afecta a las otras sucursales?**
R: Sí. Cada sucursal cierra de manera independiente. Cerrar Sucursal Americas no toca a Villa Fontana.

**P: ¿Los cortes históricos van a cambiar si presiono "Regenerar histórico"?**
R: Sí, recalculan TODOS los cortes con la lógica actual. Solo úsalo cuando se haya implementado un fix importante que requiera rebalancear datos pasados. Te avisará con un diálogo de confirmación.

**P: Si reabro un corte ya impreso y firmado, ¿qué pasa con el papel firmado?**
R: El papel pasa a ser referencia histórica. El corte oficial es el que vuelva a generarse al cerrar de nuevo. Recomendable archivar ambos con una nota explicando por qué se reabrió.

**P: ¿Qué pasa si un mismo cliente paga su apartado en 3 abonos durante 2 semanas?**
R: Los 3 abonos individuales **NO aparecen** en los cortes de los días en que se cobraron. Solo el día que termina de pagar y se lleva el equipo, **toda la suma** aparece consolidada en el corte de ese día. Si los abonos fueron mixtos (parte cash, parte card), se respeta cada método en su columna del corte.

**P: ¿Hay manera de bloquear el botón "Reabrir" para no-admins?**
R: Sí. El botón "Reabrir" solo es visible para usuarios con el permiso `business_settings.access` (típicamente admins). Los cajeros solo ven "Cerrar caja".

---

## 6. Soporte

Si encuentras un caso que no está en esta guía:
1. Toma una captura de pantalla del problema.
2. Anota la sucursal, la fecha del corte y la hora aproximada.
3. Reportar a administración.

**No intentes editar la base de datos directamente.** Cualquier modificación manual sin pasar por el sistema puede corromper la conciliación.
