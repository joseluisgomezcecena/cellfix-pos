<?php

return [
    'name' => 'Cellphone',
    'module_version' => '1.0',

    /**
     * Field mapping configuration
     * Maps cellphone-specific fields to product table columns
     */
    'field_mapping' => [
        'marca' => 'product_custom_field1',      // Brand
        'modelo' => 'product_custom_field2',     // Model
        'ubicacion' => 'product_custom_field3',  // Physical location/rack
        'estado' => 'product_custom_field4',     // Condition (Nuevo/Usado/Reacondicionado)
        'observaciones' => 'product_custom_field5', // Additional notes
        'cellphone_flag' => 'product_custom_field6', // Module identifier flag
    ],

    /**
     * Cellphone identifier flag value
     * This flag marks products as being managed by the Cellphone module
     */
    'cellphone_flag_value' => 'CELLPHONE_MODULE',

    /**
     * Warranty options (in months)
     * These will be seeded if they don't exist
     */
    'warranty_options' => [
        ['duration' => 3, 'duration_type' => 'months', 'name' => 'Garantía 3 Meses'],
        ['duration' => 6, 'duration_type' => 'months', 'name' => 'Garantía 6 Meses'],
        ['duration' => 12, 'duration_type' => 'months', 'name' => 'Garantía 12 Meses'],
    ],

    /**
     * Product type for cellphones
     * Using 'single' type with variations for Color and Capacidad
     */
    'product_type' => 'single',

    /**
     * IMEI validation pattern
     * IMEI format: 15 alphanumeric characters
     */
    'imei_pattern' => '/^[A-Z0-9]{15}$/i',

    /**
     * Default variation templates for cellphones
     */
    'default_variations' => [
        'color' => ['Negro', 'Blanco', 'Azul', 'Rojo', 'Verde', 'Gris', 'Oro', 'Plata'],
        'capacidad' => ['32GB', '64GB', '128GB', 'GB', '512GB', '1TB'],
    ],

    /**
     * Estado/Condition options
     */
    'estado_options' => [
        'nuevo' => 'Nuevo',
        'usado' => 'Usado',
        'reacondicionado' => 'Reacondicionado',
    ],
];
