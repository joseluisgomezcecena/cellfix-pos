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
    'imei_invalid' => 'El formato del IMEI es inválido. Debe contener 15 dígitos',
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
    'imei_help' => 'Ingrese el IMEI de 15 dígitos del dispositivo',
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
    'diagnostic_title' => 'Configuration Issues Detected',
    'diagnostic_intro' => 'This cellphone has configuration issues that may prevent it from appearing in the POS or working correctly:',
    'diagnostic_missing_flag' => 'Missing cellphone module flag - This product may have been edited in the main Products view, removing the cellphone identifier',
    'diagnostic_stock_disabled' => 'Stock tracking is disabled - Enable stock tracking for this product to appear in POS',
    'diagnostic_no_locations' => 'Not linked to any business locations - Product must be assigned to at least one location to appear in POS',
    'diagnostic_solution' => 'How to fix:',
    'diagnostic_command' => 'Run this command to automatically repair all broken cellphones',

    // Stock Management
    'stock_management' => 'Stock Management',
    'current_stock' => 'Current Stock',
    'location' => 'Location',
    'quantity_available' => 'Quantity Available',
    'adjust_stock' => 'Adjust Stock',
    'add_stock' => 'Add Stock',
    'remove_stock' => 'Remove Stock',
    'stock_adjustment' => 'Stock Adjustment',
    'adjustment_type' => 'Adjustment Type',
    'adjustment_quantity' => 'Quantity',
    'add' => 'Add',
    'subtract' => 'Subtract',
    'stock_updated_success' => 'Stock updated successfully',
    'invalid_stock_quantity' => 'Invalid stock quantity',
    'insufficient_stock' => 'Insufficient stock for removal',
    'no_stock_locations' => 'No stock locations found. Add stock for at least one location.',

    // Price Management
    'pricing' => 'Pricing',
    'purchase_price' => 'Purchase Price',
    'purchase_price_inc_tax' => 'Purchase Price (Inc. Tax)',
    'sell_price' => 'Sell Price',
    'sell_price_inc_tax' => 'Sell Price (Inc. Tax)',
    'profit_percent' => 'Profit %',
    'update_prices' => 'Update Prices',
    'prices_updated_success' => 'Prices updated successfully',

    // Stock & Pricing Section
    'stock_and_pricing' => 'Stock & Pricing',
    'stock_info' => 'Manage stock levels and update prices for this cellphone',
    'add_to_location' => 'Add to Location',
    'select_location' => 'Select Location',
    'initial_quantity' => 'Initial Quantity',
];
