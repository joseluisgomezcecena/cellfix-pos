# Reporte de bugs — análisis y clasificación por origen

**Fecha:** 2026-07-02
**Alcance:** Bugs reportados por el equipo operativo de Celfix durante las últimas sesiones de trabajo, con análisis técnico de causa raíz y clasificación por origen.

---

## Lista completa de bugs reportados

### Bug 1 — Cortes muestran cortes del día anterior en la mañana

- **Reportado por:** el equipo, a las 8 AM del 11/06/2026
- **Causa técnica:** El diseño anterior no creaba filas de cortes hasta la noche del cierre; en la mañana no había registro de "hoy" todavía y la pantalla mostraba solo lo de ayer.
- **Origen:** UX (el sistema hacía lo esperado, pero no lo mostraba como el equipo esperaba)
- **Complejidad:** Baja
- **Solución aplicada:** Se agregó helper `ensureTodayCutsExist()` que crea filas vacías al abrir `/daily-cuts` cada mañana.

### Bug 2 — Dropdown en `/daily-cuts/weekly` con IDs equivocados

- **Reportado por:** el equipo intentando filtrar por sucursal
- **Causa técnica:** `Collection::merge()` de Laravel usa `array_merge()` internamente, que **reindexa** las claves numéricas de un array. Los IDs reales (6, 7, 8) se convertían en (0, 1, 2). Al filtrar por "Sucursal Americas" el sistema mandaba `location_id=0`.
- **Origen:** Bug técnico oscuro de Laravel/PHP
- **Complejidad:** Muy oculta — el comportamiento del `merge` no está en primera línea de la documentación; fue difícil de reproducir sin ejecutar el código exacto.
- **Solución aplicada:** Cambio de `Collection::merge()` por el operador de unión de arrays `+` en la vista.

### Bug 3 — iPhone aparece disponible aunque ya se vendió; correcciones se tuvieron que hacer 2 veces

- **Reportado por:** el equipo, al ver que un equipo vendido seguía apareciendo en inventario
- **Causa técnica:** El usuario **Fernando Perez** creaba `purchase_transfer` desde el Almacén hacia sucursales manualmente después de que el equipo ya se había vendido. El sistema aceptaba transfers de productos con stock 0 en origen, generando "stock fantasma" en destino.
- **Origen:** Mezcla — el usuario hacía transfers manuales incorrectos + el código no validaba
- **Complejidad:** Alta — requirió reconstruir el timeline completo cruzando `purchase_lines`, `sell_lines`, `stock_corrections` de varios días para descifrar exactamente qué pasó.
- **Solución aplicada:** Validación en `StockTransferController::store` que bloquea el transfer si el origen no tiene stock suficiente.

### Bug 4 — Venta a las 6:17 PM no aparece en el corte

- **Reportado por:** un gerente que revisó su corte al día siguiente y vio que faltaba una venta
- **Causa técnica:** El heartbeat auto-cierra los cortes a las 18:00. Una vez que `closed_at` no es NULL, el corte no se regenera aunque después entren ventas. La venta a las 18:17 quedó en `transactions` pero fuera del corte.
- **Origen:** Diseño legítimo — es la regla que se decidió intencionalmente (los cortes se "congelan" al cierre), pero el equipo operativo no lo entendió.
- **Complejidad:** Media — hay que decidir política (¿reabrir manualmente? ¿auto-detectar desfases y avisar?)
- **Solución propuesta:** Documentado en `docs/guia-cortes-diarios.md`. Alternativa a futuro: agregar alerta cuando un cut cerrado difiere de las transacciones reales.

### Bug 5 — Ventas por categoría en el corte mostraban $0 en "Servicios"

- **Reportado por:** la cajera al comparar el conteo físico contra el corte
- **Causa técnica:** La fórmula SQL del sumatorio hacía `unit_price_inc_tax × quantity − line_discount_amount`. UltimatePOS ya guarda `unit_price_inc_tax` **con el descuento aplicado**. Restar el descuento una segunda vez daba $0 en líneas con descuento.
- **Origen:** Bug del código (herencia de la arquitectura de UltimatePOS con 4 columnas de precio)
- **Complejidad:** Muy sutil — la fórmula parecía correcta a simple vista.
- **Solución aplicada:** Cambio a `unit_price_inc_tax × quantity` (sin restar de nuevo el descuento).

### Bug 6 — Reporte de técnicos mostraba $0 en líneas con descuento

- **Reportado por:** el equipo al revisar comisiones
- **Causa técnica:** Idéntica al Bug 5 — otro lugar del código con la misma fórmula errada.
- **Origen:** Bug del código (mismo patrón que Bug 5)
- **Complejidad:** Baja una vez identificado el Bug 5
- **Solución aplicada:** Mismo fix que Bug 5, en `TechnicianController`.

### Bug 7 — Total ventas + Efectivo + USD + Tarjeta no cuadran (double count del USD)

- **Reportado por:** el equipo al revisar la vista del corte
- **Causa técnica:** Al arreglar la visualización previa, se agregó USD como columna aparte, pero `total_cash` ya lo incluía (los dólares se guardan convertidos a MXN dentro del `amount` del pago). Se estaba mostrando dos veces.
- **Origen:** Confusión inicial de UX + la BD guarda cash+USD juntos en un solo campo
- **Complejidad:** Media — requirió entender la distinción bruto/neto y cambio entregado
- **Solución aplicada:** Se rediseñó la vista con reconciliación explícita: MXN bruto + USD − Cambio + Tarjeta = Total ventas.

### Bug 8 — Cash en corte diferente al conteo físico ($10,500 vs $12,608)

- **Reportado por:** la cajera al hacer el conteo físico del cajón
- **Causa técnica:** El cliente pagaba con billete mayor y la cajera le daba cambio. UltimatePOS guarda el cambio como `transaction_payment` con `is_return=1`. El corte resta returns del `total_cash`, pero la vista de denominaciones NO los resta (solo suma billetes recibidos).
- **Origen:** Diseño del sistema chocando con lo que la cajera esperaba ver — bruto (billetes que pasaron) vs neto (lo que queda en el cajón)
- **Complejidad:** Media — hay que agregar columna "cambio entregado" para hacer la reconciliación visible
- **Solución aplicada:** Info-box con "Cambio entregado" en negativo + banner de reconciliación al pie.

### Bug 9 — Devoluciones: el dinero regresado no aparece en ningún reporte

- **Reportado por:** la cajera con el caso de Daniel Brambila (devolución de $3,350 en efectivo el 01/07)
- **Causa técnica múltiple:**
  1. El formulario de devolución **no tiene sección para registrar el reembolso** (solo dice qué productos regresan)
  2. El paso "Agregar pago" existe pero está **escondido** en un menú dropdown de "Acciones"
  3. `DailyCutUtil` no cuenta transacciones de tipo `sell_return` (solo `sell`)
  4. La cajera hacía **workaround**: editaba el precio del item devuelto para reducir el monto a lo que había dado en efectivo, en vez de registrar el reembolso parcial correctamente
- **Origen:** Combinación grave — bug del código + flujo confuso de UltimatePOS + workaround de cajera + confusión terminológica ("Cliente debido: −$3,350" es realmente crédito a favor del cliente)
- **Complejidad:** **Muy alta** — 4 problemas simultáneos, requiere respuestas operativas del negocio antes de arreglar
- **Estado:** Reporte pendiente en `docs/reporte-devoluciones.md` con 6 preguntas al equipo. No se ha aplicado ningún fix aún.

### Bug 10 — Apartados no se suman en `/sells` ni en dashboard ni en reporte de técnicos

- **Reportado por:** la cajera y el gerente al comparar la venta liquidada contra el corte
- **Causa técnica:** El commit `98ce00f` (09/06/2026) cambió la regla del corte para consolidar apartados en el día de liquidación. **Esa regla no se aplicó a las otras 4 pantallas** que también muestran ventas por fecha (`/sells`, `/sales-dashboard`, reporte de vendedor, reporte de técnicos).
- **Origen:** Error propio de scope — no propagué el cambio de diseño a todos los lugares que dependían de él
- **Complejidad:** Media — hay que aplicar la misma lógica a 4 archivos que usan `transaction_date` como filtro
- **Solución aplicada:** Regla propagada a `SellController::index`, `SalesDashboardController`, `VendorReportController` y `TechnicianController`.

### Bug 11 — Comisión va al vendedor equivocado (al que apartó, no al que liquidó)

- **Reportado por:** el gerente al ver que le llegaba comisión a Karla cuando en realidad Stoickoov cerró la venta
- **Causa técnica:** `SalesDashboardController` y `VendorReportController` usan `transactions.created_by` como identidad del vendedor. Para apartados, ese `created_by` es la persona que apartó, no la que liquidó. Debería usarse `layaway_payments.processed_by` del último pago.
- **Origen:** Error propio — cuando implementé el módulo de apartados no consideré esta distinción para comisiones
- **Complejidad:** Baja una vez identificado
- **Solución aplicada:** Consulta correlacionada en la SELECT que devuelve el `processed_by` del último `layaway_payment` cuando la transacción tiene `layaway_id`.

### Bug 12 — Traducciones inconsistentes (unos ven inglés, otros español)

- **Reportado por:** el equipo al ver que algunos usuarios veían textos mezclados
- **Causa técnica:** `users.language` estaba mal configurado — 56 usuarios en `en`, 4 en `es`. Los archivos `lang/en/` tenían textos ya en español mezclados, `lang/es/` tenía huecos donde el código llamaba a claves que no existían.
- **Origen:** Configuración histórica por usuario nunca alineada + traducciones de UltimatePOS incompletas
- **Complejidad:** Baja
- **Solución aplicada:** Middleware `Language` forzado a `es` para todos + agregadas ~200 claves faltantes.

---

## Desglose por origen del bug

### 🔴 Bugs del código (arquitectónicos o de fórmula) — 5

| # | Descripción | Por qué cuesta arreglar |
|---|---|---|
| 2 | `Collection::merge` reindexa | Comportamiento oscuro de Laravel |
| 5 | Fórmula double-discount en corte | Herencia de UltimatePOS con 4 columnas de precio (`unit_price`, `unit_price_before_discount`, `unit_price_inc_tax`, `line_discount_amount`) |
| 6 | Misma fórmula en reporte de técnicos | Idem |
| 10 | Apartados sin regla en 4 pantallas | Yo mismo cambié el diseño de un lado pero no propagué a los demás |
| 11 | Comisión al que apartó | Yo mismo cuando implementé apartados no consideré la distinción |

### 🟠 Datos viejos / usuarios haciendo cosas "mal" — 3

| # | Descripción | Por qué existe |
|---|---|---|
| 3 | Stock fantasma por transfers | Usuario Fernando creaba transfers sin validar stock — el código lo permitía |
| 9 | Devolución con precio editado | Cajera hacía workaround manual porque el flujo oficial no cabía en su cabeza |
| 12 | Traducciones inconsistentes | Configuración histórica con `users.language` mezclado |

### 🟡 Confusión de cajeras / UX opaca (el código estaba haciendo lo "correcto") — 4

| # | Descripción | Solución típica |
|---|---|---|
| 1 | Cortes de ayer al llegar en la mañana | UX — se resolvió con auto-create rows |
| 7 | Double count del USD (bug de display) | Rediseñar vista de reconciliación |
| 8 | Cambio entregado no visible | Agregar columna explícita |
| 9 | "Cliente debido: −$3,350" (parte del Bug 9) | Cambiar label a "Saldo a favor del cliente" |

### ⚪ Decisiones de diseño legítimas mal comunicadas — 1

| # | Descripción | Estado |
|---|---|---|
| 4 | Corte se congela al cerrar, venta tardía queda fuera | Documentado en `docs/guia-cortes-diarios.md` pero el equipo no lo lee |

---

## Por qué en general son complicados

1. **UltimatePOS tiene múltiples columnas para el mismo dato** (`unit_price`, `unit_price_before_discount`, `unit_price_inc_tax`, `line_discount_amount`) sin documentación clara de cuál usar cuándo. Los desarrolladores previos mezclaron fórmulas y quedaron bugs sutiles.

2. **Los flujos operativos de sucursal no coinciden con los flujos que UltimatePOS asume.** Ejemplo: la cajera hace devolución parcial + crédito para futura venta, pero UltimatePOS lo ve como 2 operaciones separadas con el paso "Agregar pago" escondido en un dropdown.

3. **Las mismas reglas viven duplicadas en 5+ archivos** (regla de apartados, regla de descuentos, regla de fechas). Un fix en un archivo no propaga automáticamente a los otros. Es fácil olvidarse de sincronizar todos.

4. **La BD tiene datos "corruptos" heredados**:
   - Stock fantasma (Bug 3)
   - Devoluciones sin pago (Bug 9)
   - Apartados con `completed_at` NULL (fix aplicado en commit `a50a616`)
   - Cortes con snapshots viejos

   Cada bug nuevo requiere primero limpiar/entender los datos históricos.

5. **No hay tests automatizados.** Cada cambio hay que verificar manualmente con SQL para no romper otras cosas. La suite de pruebas manuales toma horas.

6. **Los usuarios inventan workarounds cuando el flujo no cabe en su cabeza.** Ejemplos:
   - Fernando con transfers desde Almacén sin stock
   - Cajera con precios editados en devoluciones
   - Cortes cerrados manualmente cuando el sistema no consolidaba bien

   Esos workarounds ensucian los datos y no son visibles hasta que se hace auditoría.

7. **Reglas de negocio no escritas.** Cada bug importante requiere primero pregunta al negocio antes de codear:
   - ¿USD se cuenta como dólares o convertido a MXN?
   - ¿Dónde vive el dinero de anticipos de apartados?
   - ¿Quién recibe comisión de apartados: el que apartó o el que liquidó?
   - ¿Devoluciones deben requerir autorización de admin?
   - ¿Los transfers de stock deben validar origen?

---

## Recomendaciones para mitigar bugs futuros

### Corto plazo

1. **Documentar los flujos operativos correctos** en `docs/` para que el equipo pueda consultarlos. Ejemplos: guía de devoluciones, guía de apartados.

2. **Capacitación al equipo** sobre los cambios recientes (cortes congelados a las 18:00, apartados que aparecen el día de liquidación, etc.).

3. **Auditorías mensuales** de:
   - Stock fantasma (buscar transfers con origen en 0)
   - Devoluciones colgadas (`sell_return` con `payment_status = due`)
   - Apartados con `completed_at = NULL` en status `completed`

### Mediano plazo

4. **Centralizar las reglas de negocio en helpers reutilizables.** Por ejemplo, un helper `salesQuery($start, $end, $location_id)` que aplique la regla de apartados una sola vez, y sea llamado desde `/sells`, dashboard, reporte de vendedor, técnico y corte. Elimina el problema de sincronizar 5 archivos.

5. **Escribir tests automatizados** para al menos las 3 fórmulas críticas: cálculo de línea con descuento, consolidación de apartados, cálculo de balance de cliente.

### Largo plazo

6. **Reemplazar workarounds con flujos guiados.** Ejemplo: al crear una devolución, obligar a la cajera a registrar el reembolso en el mismo formulario (no en un paso separado escondido).

7. **Visibilidad de anomalías.** Un dashboard admin que muestre en tiempo real:
   - Stock negativo
   - Devoluciones sin cerrar
   - Cortes con desfase vs `SUM(transactions)`
   - Apartados vencidos

Cada anomalía visible se corrige antes de que se acumule.

---

## Archivos afectados por los fixes aplicados

Los fixes de esta ronda modifican estos archivos (para reference al hacer deploy):

- `app/Http/Controllers/SellController.php` — Bug 10
- `app/Http/Controllers/SalesDashboardController.php` — Bug 10 + 11
- `app/Http/Controllers/VendorReportController.php` — Bug 10 + 11
- `app/Http/Controllers/TechnicianController.php` — Bug 5 + 10
- `app/Http/Controllers/DailyCutController.php` — Bug 1 (previo)
- `app/Http/Controllers/StockTransferController.php` — Bug 3 (previo)
- `app/Utils/DailyCutUtil.php` — Bugs 5, 7 (previos)
- `app/Http/Middleware/Language.php` — Bug 12
- `Modules/Layaway/Http/Controllers/LayawayController.php` — feature nueva de `/equipos-apartados`
- `Modules/Layaway/Resources/views/reserved_equipos.blade.php` — feature nueva
- `resources/views/daily_cut/index.blade.php` — Bugs 7, 8
- `resources/views/daily_cut/show.blade.php` — Bugs 7, 8, 10 (previos)
- `resources/views/daily_cut/weekly.blade.php` — Bug 2 (previo)
- `resources/views/sale_pos/partials/cash_payment_modal.blade.php` — UX del exchange rate
- `public/js/pos.js` — UX (prefill mobile)
- Múltiples archivos `lang/es/*.php` — Bug 12

Sin cambios de BD para esta ronda.
