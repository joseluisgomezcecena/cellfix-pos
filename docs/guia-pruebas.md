# Guía de pruebas — Funcionalidad nueva del POS Celfix

Checklist para validar todo lo agregado en `feature/pos-improved`. Marca cada caja al pasar.

## Antes de empezar

- [ ] Limpia cachés: `php artisan view:clear && php artisan route:clear` (o borra `storage/framework/views/*` y `bootstrap/cache/*.php` en el servidor).
- [ ] Inicia sesión como **administrador** (para ver todos los menús).
- [ ] Recarga con **Ctrl + F5** (toma el JS nuevo del POS).
- [ ] Ideal: ten también un usuario con rol **VENDEDOR** para probar permisos/nombres.

---

## 1. Técnicos (CRUD)  → menú Configuración → Técnicos · `/technicians`
- [ ] Agregar técnico (nombre, teléfono, comisión por reparación, sucursales).
- [ ] Editar y desactivar.
- [ ] Aparece en la lista (DataTable con búsqueda).

## 2. Reparaciones en el POS  → `/pos/create`
- [ ] Agrega un producto de **Reparación**.
- [ ] En el recuadro del técnico aparecen: **técnico**, **fecha de entrada**, **anticipo**.
- [ ] Captura anticipo y técnico, cobra la venta.
- [ ] Verifica que la venta guarda (sin error).

## 3. Reporte de técnicos + Excel  → menú Reportes → Reporte de técnicos · `/technicians/report`
- [ ] Filtra por semana/técnico.
- [ ] Columnas: TOTAL, ANTICIPO, DEBE, TIPO DE PAGO, FECHA ENTRADA/SALIDA, TIPO DE CAMBIO, TOTAL EN PESOS.
- [ ] Botón **Exportar a Excel** descarga el archivo con esas columnas.

## 4. Metas y comisiones  → menú Configuración → Metas y comisiones · `/commission-targets`
- [ ] Lista de vendedores (usuarios con rol VENDEDOR) con nivel y sucursal.
- [ ] Clic en "Configurar metas" → captura meta y comisión por marca.
- [ ] Guarda y vuelve; la columna "metas configuradas" se actualiza.

## 5. Reporte semanal de vendedores + Excel  → menú Reportes · `/vendor-reports/weekly`
- [ ] Filtra semana/sucursal.
- [ ] Tabla TOTAL + una por vendedor (días × marcas).
- [ ] **Exportar a Excel** descarga con una hoja por vendedor.

## 6. Terminales de tarjeta (CRUD)  → menú · `/card-terminals`
- [ ] Agrega una terminal (nombre, banco).
- [ ] Aparece como opción al cobrar con tarjeta en el POS.

## 7. Tipo de cambio  → menú Configuración → Tipo de cambio · `/exchange-rate`
- [ ] Cambia el tipo de cambio (USD→MXN) y guarda.
- [ ] Verifica que se refleja en el modal de pago en efectivo del POS.

## 8. Pago en efectivo (denominaciones + dólares)  → `/pos/create`
- [ ] Cobra una venta en **efectivo** → abre el modal verde.
- [ ] Captura billetes en **pesos** y **dólares**, ajusta el **tipo de cambio** (ej. 18.50).
- [ ] El tipo de cambio se muestra con **2 decimales** y el total recibido cuadra.
- [ ] Paga sin error; verifica el cambio (vuelto).

## 9. Cortes diarios  → menú · `/daily-cuts`
- [ ] **Generar** corte del día (botón generar) por sucursal.
- [ ] Ver el detalle (`/daily-cuts/{id}`): efectivo, tarjeta, transferencia, gastos, # transacciones.
- [ ] Ver corte **semanal** (`/daily-cuts/weekly`).
- [ ] **Exportar** a Excel (diario y semanal).

## 10. Descuentos por sucursal  → menú Promociones/Descuentos · `/discounts`
- [ ] Crea/edita un descuento y asígnale **sucursales específicas**.
- [ ] Verifica que el descuento solo aplica en esas sucursales.

## 11. Puntos de recompensa  → Productos → Agregar/Editar · `/products/create`
- [ ] El producto tiene campo **reward_points**.
- [ ] Guarda y verifica que persiste al editar.

## 12. Corrección de inventario  → menú Ajuste de Stock → Corrección de inventario · `/stock-corrections`
- [ ] **Entrada (+)** en un producto que esté en 0, con motivo (ej. Producto encontrado).
- [ ] El "Inventario actual" del producto sube.
- [ ] **Ctrl + F5** en el POS → ya te deja **vender** ese producto (sin error de "desajuste").
- [ ] **Salida (−)** baja el stock.
- [ ] La lista `/stock-corrections` muestra el historial (antes/después, motivo, usuario).

## 13. Tablero de ventas + Excel  → menú Reportes → Tablero de ventas · `/sales-dashboard`
- [ ] Filtra semana (lunes)/sucursal.
- [ ] KPIs: equipos vendidos, $ total, meta, faltan.
- [ ] Edita la **meta** semanal y guarda.
- [ ] Tablas y gráficas: equipos por día, por vendedor, resumen por categoría, categorías por vendedor, detalle.
- [ ] **Exportar a Excel** descarga 4 hojas (EQUIPOS, DETALLE EQUIPOS, CATEGORIAS, RESUMEN).
- [ ] Verifica que los totales cuadren con tu Excel manual.

---

## Pruebas cruzadas (importantes)

- [ ] **Multi-sucursal:** repite ventas en 2 sucursales y confirma que reportes/cortes separan bien por sucursal.
- [ ] **Permisos:** entra como usuario VENDEDOR y confirma qué ve y qué no.
- [ ] **Fechas:** la venta guarda con fecha de venta correcta (no fecha de cotización).
- [ ] **Inventario:** tras vender, el stock baja (en productos con respaldo de compra/apertura).

## Si algo truena

- Página en blanco / 500 → limpia cachés (paso inicial) y revisa `storage/logs/laravel.log`.
- "Unknown column / table doesn't exist" → falta correr el SQL de despliegue (`docs/deploy_produccion.sql`).
- El POS no cambia / JS viejo → **Ctrl + F5**.
- Gráficas del tablero no cargan → necesita internet (Chart.js por CDN).
