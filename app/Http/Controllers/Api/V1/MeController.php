<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Perfil del cliente autenticado. Requiere el middleware AuthCustomerApi
 * — el contact se resuelve en request->attributes->api_customer.
 */
class MeController extends Controller
{
    /**
     * GET /api/v1/me
     * Header: Authorization: Bearer <token>
     */
    public function show(Request $request): JsonResponse
    {
        $c = $request->attributes->get('api_customer');

        return response()->json([
            'success'  => true,
            'customer' => AuthController::customerPayload($c),
        ]);
    }
}
