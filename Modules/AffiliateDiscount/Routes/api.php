<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the module. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

/*
|--------------------------------------------------------------------------
| Note: POS API routes have been moved to web.php
|--------------------------------------------------------------------------
|
| The POS affiliate discount routes require session-based authentication
| (web middleware) rather than token-based authentication (api middleware).
| They have been moved to Routes/web.php to avoid middleware conflicts.
|
| If you need to add true API routes (token-based authentication) for
| external integrations, you can add them here with the 'api' middleware.
|
*/
