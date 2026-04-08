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
