<?php

return [
    'name' => 'HideCardPayment',

    'module_version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Hide Card Payment Module Configuration
    |--------------------------------------------------------------------------
    |
    | This module removes the card payment option from the POS system.
    | It uses Laravel View Composers to filter out 'card' from payment_types
    | before the views are rendered.
    |
    */

    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Payment Types to Hide
    |--------------------------------------------------------------------------
    |
    | List of payment type keys to hide from the POS system.
    | Add more payment types here if needed in the future.
    |
    */

    'hidden_payment_types' => [
        'card',
    ],
];
