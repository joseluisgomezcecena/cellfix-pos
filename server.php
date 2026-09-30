<?php

/**
 * Laravel - Router para `php artisan serve` (built-in PHP web server).
 *
 * El built-in server sirve archivos estáticos existentes DIRECTAMENTE del
 * disco, sin pasar por Laravel — eso rompe el CORS que la app Flutter Web
 * necesita para descargar imágenes desde /storage/*. Aquí interceptamos
 * esos requests y agregamos los headers CORS antes de servir el archivo.
 *
 * En producción (Apache) este archivo NO se usa: allá los headers CORS
 * los pone el .htaccess.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (strpos($uri, '/storage/') === 0) {
    $file = __DIR__.'/public'.$uri;
    if (is_file($file)) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: *');
        header('Vary: Origin');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204);
            return true;
        }

        $mimes = [
            'jpg'  => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'png'  => 'image/png',  'webp' => 'image/webp',
            'gif'  => 'image/gif',  'svg'  => 'image/svg+xml',
            'pdf'  => 'application/pdf',
        ];
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($file));
        readfile($file);
        return true;
    }
}

if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
