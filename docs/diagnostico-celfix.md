# Diagnóstico Celfix — Problemas de inventario explicados en simple

**Fecha:** 21 de mayo, 2026
**Sistema:** Celfix POS (pos.celfix.mx)
**Para:** Dirección, gerentes de sucursal y cualquier persona del equipo
**Complemento técnico:** ver `inventario-no-descuenta-diagnostico.md`

---

## Antes de empezar: ¿para qué es este documento?

Este documento explica, **sin tecnicismos**, dos problemas de inventario que se han estado
arrastrando en el sistema. La idea es que **cualquier persona** —aunque no sepa nada de
computación— entienda qué está pasando, por qué pasa, y qué vamos a hacer para arreglarlo.

Al final hay un **plan de acción paso a paso**.

---

## El resumen en una sola frase

> El programa funciona bien y descuenta lo que se vende. El problema es que **los números con
> los que arrancó el sistema (cuando se cambió del programa viejo al nuevo) venían mal**, y eso
> hace que hoy el inventario no coincida con lo que físicamente hay en las tiendas.

No es que el sistema "se coma" las ventas. Es que **arrancó con datos torcidos** y eso se nota
hasta hoy.

---

## Primero, entendamos cómo funciona el inventario (con una analogía)

Imagina que cada producto tiene un **cuaderno de control** en cada sucursal. En ese cuaderno
se anota:

- **Entradas (+):** cuando llega mercancía (stock inicial, compras, traspasos de otra sucursal).
- **Salidas (−):** cuando se vende algo o se traspasa a otra sucursal.

El número que queda al final (entradas menos salidas) es lo que el sistema cree que tienes.
Ese número es el que el punto de venta usa para dejarte vender. Si el cuaderno dice **0**,
el sistema **no te deja vender**, aunque físicamente tengas el producto en la mano.

Hasta aquí todo bien. El programa hace estas cuentas correctamente. **El problema está en cómo
se llenó ese cuaderno al principio.**

---

# BUG #1 — El inventario que no cuadra (heredado de la migración)

## ¿Qué se ve?

- Vendes un producto y "parece" que no se descuenta.
- O peor: el sistema dice que tienes **0** de algo que **sí tienes físicamente**, y por eso
  **no te deja venderlo**.
- Y cuando intentas corregirlo en la pantalla de "Stock de apertura", pones **2** y te aparece **1**.

## ¿Qué está pasando en realidad? (la analogía)

Cuando se pasó del programa viejo al programa nuevo (la "migración"), fue como **copiar el cuaderno
viejo a uno nuevo, pero a la carrera y con errores**:

1. En algunos productos se anotó **más de lo que había** (el cuaderno nuevo dice 500 cuando en
   realidad eran 4).
2. En otros se anotó **menos de lo que había** (dice 0 cuando físicamente hay 1).
3. Y lo más raro: **se copiaron ventas viejas con su fecha original**, pero el "stock inicial"
   se anotó con la fecha del día que se hizo la migración.

El punto 3 es el más dañino. Es como si en el cuaderno apareciera:
*"Vendí este teléfono en septiembre 2025"* … pero la línea de *"recibí este teléfono"* dice
*"abril 2026"*. **¿Cómo vendiste en septiembre algo que recibiste hasta abril?** Imposible. Eso
es una señal clara de que los datos se mezclaron en la migración.

## Caso real: LCD IPHONE 15 PRO MAX OLED en Sucursal Nuevo Mexicali

Este es el ejemplo perfecto porque junta todo. En esa sucursal, el cuaderno dice:

- **Entró:** 7 piezas (stock inicial, fechado en abril 2026)
- **Salió:** 7 piezas → 6 ventas + 1 traspaso a otra sucursal

7 − 7 = **0**. Por eso el sistema marca 0 y no deja vender.

**Pero hay dos cosas mal:**

1. Una de esas "ventas" está fechada en **septiembre 2025**, ¡siete meses antes de que el producto
   "entrara" en abril 2026! Eso es la huella de la migración: una venta vieja pegada encima de un
   stock nuevo.

2. La pantalla de **"Stock de apertura"** muestra **1**, pero el inventario real muestra **0**.
   ¿Por qué la diferencia? Porque esa pantalla **no cuenta los traspasos entre sucursales**. La
   pieza que se traspasó (1) sí salió del inventario real, pero el contador de la pantalla de
   apertura no se enteró. Por eso una pantalla dice 1 y la otra dice 0.

## ¿Por qué "agrego 2 y me aparece 1"?

Por lo mismo de arriba: la pantalla de "Stock de apertura" tiene un contador que arrastra ventas
y traspasos viejos de la migración. Cuando pones 2, el sistema le resta cosas que ya traía
contadas, y te queda 1. **La pantalla de apertura no es de fiar para corregir inventario.**

## La regla de oro para todos

- ✅ **El número bueno es "Inventario actual" / "Stock actual"** (es el que manda para vender).
- ❌ **No corrijan inventario en "Stock de apertura"** — esa pantalla engaña.
- ✅ **Para corregir, usen "Ajustes de inventario"** — ahí se pone la cantidad real y queda un
  registro limpio de quién y cuándo lo ajustó.

---

# BUG #2 — Los equipos viejos (SKU de 4 a 6 dígitos)

## ¿Qué son estos equipos?

Hace tiempo, los teléfonos (equipos) se dieron de alta en el sistema con un código (SKU) corto,
tipo **`CF-1234`** (la parte numérica son los últimos dígitos del IMEI del teléfono). Cada código
representa **un teléfono físico único**.

En total hay **3,560 de estos equipos viejos** en el sistema.

## ¿Qué les pasó?

Según la historia del equipo: estos teléfonos **se vendían y sí se descontaban** sin problema…
hasta que **hubo una falla** ("un picadero", como lo describieron) y a partir de ahí **muchos
equipos se vendieron pero el sistema no los descontó**. Combina eso con los errores de la
migración (Bug #1) y el resultado es un inventario de equipos poco confiable.

## Lo que muestran los números reales

Revisando la base de datos completa de producción:

- **3,560** equipos viejos en total.
- **3,076 (el 86%)** ya tienen al menos una venta registrada → es decir, **la gran mayoría ya
  se vendieron**. Esto le da la razón a la dirección cuando dice "esos ya están vendidos".
- **342** tienen una venta con fecha **anterior** a su stock inicial → imposible, otra vez la
  huella de la migración.
- **354** equipos **todavía aparecen "en stock"** según el sistema, repartidos así:

| Sucursal | Equipos que el sistema cree en stock |
|---|---|
| Sucursal Americas | 28 |
| Sucursal Nuevo Mexicali | 58 |
| Sucursal Villa Fontana | 145 |
| Sucursal Benito Juárez | 119 |
| Almacén Equipos | 4 |
| **TOTAL** | **354** |

De esos 354, una parte serán reales (siguen en la tienda) y otra parte serán "fantasma" (ya se
vendieron pero no se descontaron). **Solo el conteo físico puede decir cuáles son cuáles.**

## El entregable para resolverlo

Ya se generó un **Excel** con esos 354 equipos, una hoja por sucursal, para que cada gerente
marque cuáles tiene físicamente y cuáles ya se vendieron:

> `docs/relacion_equipos_2026-05-21.xlsx`

---

## Algo importante: ¿es culpa del programa?

**No.** Lo verificamos con pruebas directas: cuando hoy se hace una venta, el sistema **sí
descuenta** correctamente. De hecho, al revisar TODA la base de datos de producción, solo el
**~1%** de los registros de inventario tienen un descuadre. El programa está bien.

El problema es **de los datos con los que arrancó**, no del programa. Es como tener una
calculadora que funciona perfecto, pero si le metes números equivocados, el resultado sale
equivocado. La calculadora (el programa) está bien; los números iniciales (la migración) no.

---

# PLAN DE ACCIÓN DETALLADO

El objetivo: dejar el inventario **confiable y al día**, empezando por los equipos (lo más
importante para el negocio), y evitar que vuelva a pasar.

### Fase 0 — Respaldo (LISTO ✅)

- Se hizo un respaldo completo de la base de datos de producción.
- Se cargó en un ambiente aislado de pruebas para analizar sin tocar el sistema en vivo.
- **Responsable:** equipo técnico. **Estado:** completado.

### Fase 1 — Relación de equipos (LISTO ✅)

- Se generó el Excel con los **354 equipos** que el sistema cree en stock, separados por sucursal.
- Incluye columna de "última venta registrada" como pista, y una columna en blanco para que el
  gerente marque el estado físico.
- **Responsable:** equipo técnico. **Estado:** completado. **Entregable:** `relacion_equipos_2026-05-21.xlsx`.

### Fase 2 — Conteo físico de equipos (acción de las sucursales)

- La dirección sube el Excel a Google Drive (una hoja por sucursal).
- Cada **gerente** recorre físicamente su tienda y marca, equipo por equipo:
  - **PRESENTE** (lo tengo físicamente)
  - **VENDIDO** (ya se vendió)
  - **NO ENCONTRADO** (no aparece, investigar)
- **Responsable:** gerentes de sucursal. **Tiempo estimado:** 2 a 4 días según el tamaño de la tienda.

### Fase 3 — Corrección masiva con trazabilidad (acción técnica)

- Con los Excel llenos, el equipo técnico aplica las correcciones mediante **"Ajustes de
  inventario"** (no tocando la pantalla de apertura):
  - Equipo marcado **PRESENTE** pero el sistema dice 0 → se ajusta a la cantidad real.
  - Equipo marcado **VENDIDO** pero el sistema todavía lo muestra → se ajusta a 0.
- Cada ajuste queda **registrado** (quién, cuándo, por qué), para auditoría.
- **Responsable:** equipo técnico. **Tiempo estimado:** 1 día una vez recibidos los conteos.

### Fase 4 — Extender al resto del inventario (accesorios, refacciones)

- Repetir el mismo proceso (relación → conteo → ajuste) para las demás categorías,
  empezando por las de mayor valor o movimiento.
- **Responsable:** técnico + gerentes. **Tiempo estimado:** por etapas, según prioridad.

### Fase 5 — Prevención (para que no vuelva a pasar)

- **Bloquear venta bajo cero:** que el sistema nunca permita vender lo que no hay (evita que el
  inventario se vaya a negativo).
- **Bloquear los SKUs viejos:** marcar los equipos antiguos (CF- de 4-6 dígitos) para que no se
  puedan vender por error; los equipos nuevos ya usan el IMEI completo (15 dígitos).
- **Alerta de stock bajo:** un reporte diario de productos por agotarse.
- **Capacitación corta a cajeros y gerentes:** usar "Ajustes de inventario" (no "Stock de
  apertura") para cualquier corrección.
- **Responsable:** técnico + dirección. **Tiempo estimado:** 1 a 2 días.

---

## En resumen

1. El **programa funciona**; el problema vino de la **migración** del sistema viejo.
2. Se manifiesta de dos formas: inventario que no cuadra (Bug #1) y equipos viejos con datos
   revueltos (Bug #2).
3. **No corregir con "Stock de apertura"** — usar **"Ajustes de inventario"**.
4. El camino para arreglarlo es **conteo físico + ajuste con registro**, empezando por equipos.
5. Ya está lista la herramienta (el Excel) para que las sucursales empiecen el conteo.

---

## Mini-glosario (por si acaso)

- **SKU:** el código que identifica a un producto (ej. `CF-1234`).
- **IMEI:** el número único de identidad de un teléfono (como su "huella digital").
- **Stock / inventario actual:** cuántas piezas cree el sistema que tienes ahora. Es el que manda.
- **Stock de apertura:** la cantidad con la que se "abrió" el producto en el sistema. **No sirve
  para corregir inventario** porque arrastra errores de la migración.
- **Ajuste de inventario:** la herramienta correcta para corregir cantidades, con registro.
- **Traspaso:** mover mercancía de una sucursal a otra.
- **Migración:** el proceso de pasar los datos del programa viejo al nuevo (de aquí vienen los errores).
