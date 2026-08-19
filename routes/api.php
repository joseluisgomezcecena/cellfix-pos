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
});
