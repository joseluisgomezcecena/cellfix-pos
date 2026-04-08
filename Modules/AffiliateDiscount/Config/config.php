<?php

return [
    'name' => 'AffiliateDiscount',

    'module_version' => '1.0',

    /*
    |--------------------------------------------------------------------------
    | Affiliate Discount Categories
    |--------------------------------------------------------------------------
    |
    | These are the categories where discounts can be applied.
    | Maps internal keys to display names.
    |
    */
    'categories' => [
        'equipos' => 'EQUIPOS',
        'pantallas' => 'PANTALLAS',
        'accesorios' => 'ACCESORIOS',
        'desbloqueos' => 'DESBLOQUEOS',
        'servicios' => 'SERVICIOS',
        'reparaciones' => 'REPARACIONES',
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Mapping
    |--------------------------------------------------------------------------
    |
    | Maps discount categories to actual product category names in database.
    | When creating discounts, these mappings are used to find which product
    | categories should receive the discount.
    |
    | Format: 'discount_category' => ['Product Category 1', 'Product Category 2', ...]
    |
    */
    'category_mapping' => [
        'accesorios' => [
            'ACCESORIOS',
            'Protectores', 'Protector', 'PROTECTORES',
            'Cargadores', 'CARGADORES',
            'Bocinas', 'BOCINAS',
            'Audifonos', 'AUDIFONOS', 'Audífonos',
            'Cables', 'CABLES',
            'Adaptadores', 'ADAPTADORES',
            'Soportes', 'SOPORTES',
            'Micas', 'MICAS',
            'Fundas', 'FUNDAS',
            'Carcasas', 'CARCASAS',
        ],
        'equipos' => [
            'EQUIPOS',
            'Smartphones', 'SMARTPHONES',
            'Celulares', 'CELULARES',
            'Tablets', 'TABLETS',
        ],
        'pantallas' => [
            'PANTALLAS',
            'Displays', 'DISPLAYS',
            'LCD', 'OLED',
        ],
        'desbloqueos' => [
            'DESBLOQUEOS',
            'Unlock', 'UNLOCK',
        ],
        'servicios' => [
            'SERVICIOS',
            'Services', 'SERVICES',
        ],
        'reparaciones' => [
            'REPARACIONES',
            'Repairs', 'REPAIRS',
            'Reparación', 'REPARACIÓN',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    |
    | Session key for storing active discount selections during POS session.
    |
    */
    'session_key' => 'affiliate_discount_selections',

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | TTL for caching affiliated business data (in seconds).
    |
    */
    'cache_ttl' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Discount Types
    |--------------------------------------------------------------------------
    |
    | Available discount types for creating discount options.
    |
    */
    'discount_types' => [
        'fixed' => 'Fixed Amount',
        'percentage' => 'Percentage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    */
    'defaults' => [
        'max_discount_percentage' => 100,
        'max_discount_fixed' => 999999,
        'allow_multiple_discounts_per_category' => false, // Use radio buttons
    ],
];
