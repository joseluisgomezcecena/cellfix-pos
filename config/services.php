<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // WhatsApp provider — usado por App\Services\WhatsApp\WhatsAppService.
    // Por default 'stub' (solo loguea). Cambiar a 'meta' cuando las credenciales
    // de Meta WhatsApp Business Cloud API estén configuradas.
    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'stub'),
        'meta' => [
            'phone_id' => env('META_WA_PHONE_ID'),
            'access_token' => env('META_WA_ACCESS_TOKEN'),
            'template_name' => env('META_WA_TEMPLATE_NAME', 'celfix_password_reset'),
            'template_lang' => env('META_WA_TEMPLATE_LANG', 'es_MX'),
        ],
    ],

];
