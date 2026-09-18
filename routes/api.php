<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// ═══════════════════════════════════════════════════════════════════
// API pública v1 — la app Flutter Celfix Socios consume estos endpoints
// sin auth. Devuelve solo data que puede ver cualquiera (sucursales,
// promos vigentes, beneficios activos).
// ═══════════════════════════════════════════════════════════════════
Route::prefix('v1')->group(function () {
    Route::get('/locations', [\App\Http\Controllers\Api\V1\PublicController::class, 'locations']);
    Route::get('/promos',    [\App\Http\Controllers\Api\V1\PublicController::class, 'promos']);
    Route::get('/benefits',  [\App\Http\Controllers\Api\V1\PublicController::class, 'benefits']);

    // Auth de clientes. Login público con rate limit para frenar brute force
    // (5 intentos/min por IP). Logout y perfil requieren token bearer.
    Route::post('/auth/login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    // Registro público con rate limit conservador (3/min por IP) para evitar
    // registros masivos automatizados.
    Route::post('/auth/register', [\App\Http\Controllers\Api\V1\AuthController::class, 'register'])
        ->middleware('throttle:3,1');

    // Recuperación de contraseña por WhatsApp. Rate-limit conservador para
    // evitar spam a la Cloud API (que tiene costo/cuota) y para no bombardear
    // al usuario si alguien intenta abusar.
    Route::post('/auth/forgot-password', [\App\Http\Controllers\Api\V1\AuthController::class, 'forgotPassword'])
        ->middleware('throttle:3,1');

    Route::middleware('auth.customer.api')->group(function () {
        Route::post('/auth/logout',          [\App\Http\Controllers\Api\V1\AuthController::class,        'logout']);
        Route::post('/auth/change-password', [\App\Http\Controllers\Api\V1\AuthController::class,        'changePassword']);
        Route::get('/me',                    [\App\Http\Controllers\Api\V1\MeController::class,          'show']);
        Route::get('/purchases',             [\App\Http\Controllers\Api\V1\PurchasesController::class,   'index']);
        Route::get('/purchases/{id}',        [\App\Http\Controllers\Api\V1\PurchasesController::class,   'show']);
        Route::get('/repair-orders',         [\App\Http\Controllers\Api\V1\RepairOrdersController::class,'index']);
    });
});
