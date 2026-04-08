<?php

return [
    // Module
    'module_name' => 'Equipos Celulares',
    'cellphone' => 'Equipo Celular',
    'cellphones' => 'Equipos Celulares',

    // Menu
    'menu' => 'Equipos Celulares',
    'all_cellphones' => 'Todos los Equipos',
    'new_cellphone' => 'Nuevo Equipo',
    'add_cellphone' => 'Agregar Equipo Celular',
    'edit_cellphone' => 'Editar Equipo Celular',
    'view_cellphone' => 'Ver Equipo Celular',

    // Fields
    'marca' => 'Marca',
    'modelo' => 'Modelo',
    'color' => 'Color',
    'capacidad' => 'Capacidad',
    'imei' => 'IMEI',
    'warranty' => 'Garantía',
    'price' => 'Precio',
    'ubicacion' => 'Ubicación',
    'estado' => 'Estado/Condición',
    'observaciones' => 'Observaciones',

    // Estado options
    'nuevo' => 'Nuevo',
    'usado' => 'Usado',
    'reacondicionado' => 'Reacondicionado',

    // Actions
    'create' => 'Crear Equipo Celular',
    'update' => 'Actualizar Equipo Celular',
    'delete' => 'Eliminar Equipo Celular',
    'view' => 'Ver Equipos Celulares',
    'export' => 'Exportar',
    'search' => 'Buscar',
    'filter' => 'Filtrar',
    'clear_filters' => 'Limpiar Filtros',

    // Messages
    'created_success' => 'Equipo celular creado exitosamente',
    'updated_success' => 'Equipo celular actualizado exitosamente',
    'deleted_success' => 'Equipo celular eliminado exitosamente',
    'not_found' => 'Equipo celular no encontrado',

    // Validation
    'imei_required' => 'El IMEI es requerido',
    'imei_invalid' => 'El formato del IMEI es inválido. Debe contener 15 caracteres alfanuméricos',
    'imei_duplicate' => 'Este IMEI ya está registrado en el sistema',
    'marca_required' => 'La marca es requerida',
    'modelo_required' => 'El modelo es requerido',
    'price_required' => 'El precio es requerido',

    // Dashboard
    'dashboard_widget_title' => 'Resumen de Equipos Celulares',
    'total_cellphones' => 'Total de Equipos',
    'by_marca' => 'Por Marca',
    'by_estado' => 'Por Estado',
    'warranties_expiring' => 'Garantías por Vencer',
    'stock_by_location' => 'Stock por Ubicación',

    // Reports
    'cellphone_report' => 'Reporte de Equipos Celulares',
    'inventory_report' => 'Inventario de Celulares',
    'warranty_report' => 'Reporte de Garantías',

    // Permissions
    'create_cellphone' => 'Crear equipo celular',
    'view_cellphone' => 'Ver equipos celulares',
    'update_cellphone' => 'Actualizar equipo celular',
    'delete_cellphone' => 'Eliminar equipo celular',
    'export_cellphone' => 'Exportar equipos celulares',

    // Tooltips
    'imei_help' => 'Ingrese el IMEI de 15 caracteres alfanuméricos del dispositivo',
    'ubicacion_help' => 'Ubicación física del equipo (ej: Estante A-3)',
    'warranty_help' => 'Seleccione el período de garantía para este equipo',

    // Placeholders
    'search_placeholder' => 'Buscar por marca, modelo o IMEI...',
    'select_marca' => 'Seleccionar Marca',
    'select_modelo' => 'Seleccionar Modelo',
    'select_warranty' => 'Seleccionar Garantía',
    'select_estado' => 'Seleccionar Estado',

    // Table headers
    'th_imei' => 'IMEI',
    'th_marca' => 'Marca',
    'th_modelo' => 'Modelo',
    'th_color' => 'Color',
    'th_capacidad' => 'Capacidad',
    'th_price' => 'Precio',
    'th_warranty' => 'Garantía',
    'th_ubicacion' => 'Ubicación',
    'th_estado' => 'Estado',
    'th_stock' => 'Stock',
    'th_actions' => 'Acciones',

    // Diagnostics
    'diagnostic_title' => 'Problemas de Configuración Detectados',
    'diagnostic_intro' => 'Este celular tiene problemas de configuración que pueden impedir que aparezca en el POS o funcione correctamente:',
    'diagnostic_missing_flag' => 'Falta la bandera del módulo de celulares - Este producto puede haber sido editado en la vista principal de Productos, eliminando el identificador de celular',
    'diagnostic_stock_disabled' => 'El seguimiento de stock está deshabilitado - Habilite el seguimiento de stock para que este producto aparezca en el POS',
    'diagnostic_no_locations' => 'No vinculado a ninguna ubicación de negocio - El producto debe asignarse al menos a una ubicación para aparecer en el POS',
    'diagnostic_solution' => 'Cómo solucionarlo:',
    'diagnostic_command' => 'Ejecute este comando para reparar automáticamente todos los celulares con problemas',

    // Stock Management
    'stock_management' => 'Gestión de Stock',
    'current_stock' => 'Stock Actual',
    'location' => 'Ubicación',
    'quantity_available' => 'Cantidad Disponible',
    'adjust_stock' => 'Ajustar Stock',
    'add_stock' => 'Agregar Stock',
    'remove_stock' => 'Remover Stock',
    'stock_adjustment' => 'Ajuste de Stock',
    'adjustment_type' => 'Tipo de Ajuste',
    'adjustment_quantity' => 'Cantidad',
    'add' => 'Agregar',
    'subtract' => 'Restar',
    'stock_updated_success' => 'Stock actualizado exitosamente',
    'invalid_stock_quantity' => 'Cantidad de stock inválida',
    'insufficient_stock' => 'Stock insuficiente para la remoción',
    'no_stock_locations' => 'No se encontraron ubicaciones de stock. Agregue stock para al menos una ubicación.',

    // Price Management
    'pricing' => 'Precios',
    'purchase_price' => 'Precio de Compra',
    'purchase_price_inc_tax' => 'Precio de Compra (Inc. Impuestos)',
    'sell_price' => 'Precio de Venta',
    'sell_price_inc_tax' => 'Precio de Venta (Inc. Impuestos)',
    'profit_percent' => 'Ganancia %',
    'update_prices' => 'Actualizar Precios',
    'prices_updated_success' => 'Precios actualizados exitosamente',

    // Stock & Pricing Section
    'stock_and_pricing' => 'Stock y Precios',
    'stock_info' => 'Gestione niveles de stock y actualice precios para este celular',
    'add_to_location' => 'Agregar a Ubicación',
    'select_location' => 'Seleccionar Ubicación',
    'initial_quantity' => 'Cantidad Inicial',

    // Product form translations (replacing product.* and lang_v1.*)
    'product_name' => 'Nombre del Producto',
    'brand' => 'Marca',
    'category' => 'Categoría',
    'sub_category' => 'Subcategoría',
    'unit' => 'Unidad',
    'barcode_type' => 'Tipo de Código de Barras',
    'applicable_tax' => 'Impuesto Aplicable',
    'selling_price_tax_type' => 'Tipo de Impuesto del Precio de Venta',
    'exclusive' => 'Exclusivo',
    'inclusive' => 'Inclusivo',
    'product_stock' => 'Stock del Producto',
    'enable_stock' => 'Habilitar Stock',
    'selling_price' => 'Precio de Venta',
    'opening_stock' => 'Stock Inicial',
    'yes' => 'Sí',
    'no' => 'No',
    'enable_stock_help' => 'Habilitar seguimiento de stock para este producto',

    // Additional UI translations
    'error' => '¡Error!',
    'active_status' => 'Estado Activo',
    'active' => 'Activo',
    'inactive' => 'Inactivo',
    'all' => 'Todos',
    'deactivate_selected' => 'Desactivar Seleccionados',
    'please_select_product' => 'Por favor selecciona al menos un producto',
    'confirm_activate' => '¿Estás seguro?',
    'confirm_activate_product' => '¿Deseas activar este producto?',
    'product_name_help' => 'Nombre completo del producto (ej: Samsung Galaxy S21 128GB Negro)',
    'opening_stock_info' => 'Agregue el inventario inicial para este celular. Si no agrega inventario aquí, puede agregarlo después mediante Compras o Ajuste de Inventario.',
    'business_location_help' => 'Ubicación del negocio donde se almacenará este celular',
    'opening_stock_qty_help' => 'Cantidad inicial en inventario (típicamente 1 para celulares)',
    'purchase_price_help' => 'Precio de compra/costo',
    'sell_price_help' => 'Precio de venta al público',
    'auto_calculated_tax' => 'Calculado automáticamente con impuesto',
    'set_to_exact' => 'Establecer cantidad exacta',
    'leave_empty_no_changes' => 'Dejar vacío para no hacer cambios',
    'add_another' => 'Agregar Otro',
    'remove' => 'Eliminar',
];
