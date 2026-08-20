<?php

namespace App\Http\Middleware;

use App\Contact;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware para endpoints protegidos de la app Celfix Socios (Flutter).
 * Espera header Authorization: Bearer <token>. El token viene de /auth/login,
 * se guarda hasheado (sha256) en contacts.app_api_token.
 *
 * Si valida: injecta el contact en request->attributes->api_customer para que
 * los controllers lo lean sin duplicar lookup.
 */
class AuthCustomerApi
{
    private const BUSINESS_ID = 2;

    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization', '');
        if (stripos($header, 'Bearer ') !== 0) {
            return $this->unauthorized();
        }

        $token = trim(substr($header, 7));
        if ($token === '') {
            return $this->unauthorized();
        }

        $hashed = hash('sha256', $token);
        $contact = Contact::where('business_id', self::BUSINESS_ID)
            ->whereIn('type', ['customer', 'both'])
            ->where('app_api_token', $hashed)
            ->first();

        if (!$contact) {
            return $this->unauthorized();
        }

        $request->attributes->set('api_customer', $contact);
        return $next($request);
    }

    private function unauthorized()
    {
        return response()->json([
            'success' => false,
            'message' => 'No autenticado.',
        ], 401);
    }
}
