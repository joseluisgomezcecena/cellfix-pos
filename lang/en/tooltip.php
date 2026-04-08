<?php

return [
   'product_stock_alert' => "Productos con bajo inventario. <br/> <small class='text-muted'>Basado en la cantidad mínima de alerta configurada al agregar el producto. Compra este producto antes que se agote el inventario.</small>",
   
   'payment_dues' => "Pagos pendientes por compras. <br/> <small class='text-muted'>Basado en el plazo de pago del proveedor.<br/>Mostrando pagos a vencer en 7 días o menos.</small>",
   
   'input_tax' => 'Total de impuestos recaudados por ventas en el período seleccionado.',
   
   'output_tax' => 'Total de impuestos pagados por compras en el período seleccionado.',
   
   'tax_overall' => 'Diferencia entre impuestos recaudados y pagados en el período seleccionado.',
   
   'purchase_due' => 'Monto total pendiente por pagar de compras.',
   
   'sell_due' => 'Monto total pendiente por cobrar de ventas',
   
   'over_all_sell_purchase' => 'Valor negativo = Monto a pagar <br> Valor positivo = Monto a recibir',
   
   'no_of_products_for_trending_products' => 'Número de productos más vendidos a mostrar en el gráfico.',
   
   'top_trending_products' => "Productos más vendidos de tu negocio. <br/> <small class='text-muted'>Aplica filtros para ver los productos más vendidos por categoría, marca, ubicación, etc.</small>",
   
   'sku' => "Identificador único del producto <br><br>Déjalo en blanco para generarlo automáticamente.<br><small class='text-muted'>Puedes modificar el prefijo en la Configuración del negocio.</small>",
   
   'enable_stock' => 'Habilitar o deshabilitar el control de inventario para este producto.',
   
   'alert_quantity' => "Recibe una alerta cuando el inventario llegue o baje de esta cantidad.<br><br><small class='text-muted'>Los productos con bajo inventario se mostrarán en el Panel Principal - Sección de alertas de inventario.</small>",
   
   'product_type' => '<b>Producto único</b>: Producto sin variantes.<br><b>Producto variable</b>: Producto con variantes como talla, color, etc.',
   
   'profit_percent' => "Margen de ganancia predeterminado para el producto.<br><small class='text-muted'>(<i>Puedes configurar el margen predeterminado en la Configuración del negocio.</i>)</small>",
   
   'pay_term' => "Pagos pendientes por compras dentro del plazo establecido.<br/><small class='text-muted'>Los pagos vencidos o por vencer se mostrarán en el Panel Principal - Sección de pagos pendientes</small>",
   
   'order_status' => 'Los productos de esta compra estarán disponibles para la venta solo si el <b>Estado del pedido</b> es <b>Artículos recibidos</b>.',
   
   'purchase_location' => 'Ubicación donde el producto comprado estará disponible para la venta.',
   
   'sale_location' => 'Ubicación desde donde deseas vender',
   
   'sale_discount' => "Configura el 'Descuento predeterminado' para todas las ventas en la Configuración del negocio. Haz clic en el ícono de editar para agregar/modificar el descuento.",
   
   'sale_tax' => "Configura el 'Impuesto predeterminado' para todas las ventas en la Configuración del negocio. Haz clic en el ícono de editar para agregar/modificar el impuesto.",
   
   'default_profit_percent' => "Margen de ganancia predeterminado para productos.<br><small class='text-muted'>Se usa para calcular el precio de venta según el precio de compra ingresado.<br/>Puedes modificar este valor para productos individuales al agregarlos</small>",
   
   'fy_start_month' => 'Mes de inicio del año fiscal',
   
   'business_tax' => 'Número de identificación tributaria del negocio.',
   
   'invoice_scheme' => "El esquema de factura define el formato de numeración. Selecciona el esquema para esta ubicación<small class='text-muted'><i>Puedes agregar nuevos esquemas en la configuración de facturas</i></small>",
   
   'invoice_layout' => "Diseño de facturas para esta ubicación<small class='text-muted'>(<i>Puedes agregar nuevos diseños en la configuración de facturas</i>)</small>",
   
   'invoice_scheme_name' => 'Nombre breve y descriptivo para el esquema de facturación',
   
   'invoice_scheme_prefix' => 'Prefijo para el esquema de facturas.<br>Puede ser un texto personalizado o el año actual. Ejemplo: #XXXX0001, #2024-0002',
   
   'invoice_scheme_start_number' => "Número inicial para la numeración de facturas.<br><small class='text-muted'>Puede ser 1 o cualquier otro número desde donde desees iniciar.</small>",
   
   'invoice_scheme_count' => 'Número total de facturas generadas con este esquema',
   
   'invoice_scheme_total_digits' => 'Longitud del número de factura sin incluir el prefijo',
   
   'tax_groups' => 'Grupos de tasas de impuestos previamente definidos, para usar en conjunto en Compras/Ventas.',
   
   'unit_allow_decimal' => 'Los decimales permiten vender productos en fracciones.',
   
   'print_label' => 'Agregar productos -> Elegir información para las etiquetas -> Seleccionar configuración de códigos de barras -> Vista previa -> Imprimir',
   
   'expense_for' => 'Selecciona el usuario relacionado con el gasto. <i>(Opcional)</i><br/><small>Ejemplo: salario de un empleado.</small>',
   
   'all_location_permission' => 'Si seleccionas <b>Todas las ubicaciones</b>, este rol tendrá acceso a todas las ubicaciones del negocio',
   
   'dashboard_permission' => 'Si no se selecciona, solo se mostrará el mensaje de bienvenida en el Panel Principal.',
   
   'access_locations_permission' => 'Selecciona las ubicaciones a las que este rol tendrá acceso. Solo verá información de las ubicaciones seleccionadas.<br/><br/><small>Ejemplo: útil para definir roles como <i>Administrador/Cajero/Encargado de inventario/Encargado de marcas</i> para una ubicación específica.</small>',
   
   'print_receipt_on_invoice' => 'Habilitar o deshabilitar la impresión automática de facturas al finalizar',
   
   'receipt_printer_type' => '<i>Impresión desde navegador</i>: Muestra el diálogo de impresión con vista previa<br/><br/><i>Usar impresora de recibos configurada</i>: Selecciona una impresora térmica configurada',
   
   'adjustment_type' => '<i>Normal</i>: Ajustes por causas comunes como pérdidas, daños, etc.<br/><br/><i>Anormal</i>: Ajustes por eventos extraordinarios como incendios, accidentes, etc.',
   
   'total_amount_recovered' => 'Monto recuperado por seguro, venta de material dañado u otros medios',
   
   'express_checkout' => 'Marcar como pago completo en efectivo y finalizar',
   
   'total_card_slips' => 'Número total de pagos con tarjeta en este corte de caja',
   
   'total_cheques' => 'Número total de cheques en este corte de caja',
   
   'capability_profile' => 'La compatibilidad de comandos y códigos de página varía según fabricante y modelo. Si no estás seguro, usa el perfil básico',
   
   'purchase_different_currency' => 'Selecciona esta opción si compras en una moneda diferente a la de tu negocio',
   
   'currency_exchange_factor' => "1 Moneda de compra = ? moneda base<br><small class='text-muted'>Puedes activar/desactivar 'Comprar en otra moneda' en la configuración del negocio.</small>",
   
   'accounting_method' => 'Método contable',
   
   'transaction_edit_days' => 'Número de días después de la fecha de transacción en que se permite editarla',
   
   'stock_expiry_alert' => "Lista de productos por vencer en :days días<br><small class='text-muted'>Puedes configurar el número de días en la Configuración del negocio</small>",
];
