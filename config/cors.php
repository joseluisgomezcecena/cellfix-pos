<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // 'storage/*' expone header CORS a los archivos públicos (fotos de perfil,
    // imágenes de promos, backgrounds de app-designs) para que Flutter Web
    // pueda dibujarlos — el web engine descarga por fetch, no por <img>, y sin
    // Access-Control-Allow-Origin el browser bloquea la textura aunque HTTP 200.
    // No expone nada nuevo: /storage/* ya se sirve público sin auth.
    // En APK Android/iOS no aplica (CORS es solo del navegador).
    'paths' => ['api/*', 'storage/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
