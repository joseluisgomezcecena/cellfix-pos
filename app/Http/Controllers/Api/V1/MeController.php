<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Perfil del cliente autenticado (app Celfix Socios).
 * Requiere el middleware AuthCustomerApi — el contact se resuelve en
 * $request->attributes->api_customer.
 */
class MeController extends Controller
{
    /**
     * GET /api/v1/me
     * Header: Authorization: Bearer <token>
     * Devuelve el perfil actual del cliente.
     */
    public function show(Request $request): JsonResponse
    {
        $c = $request->attributes->get('api_customer');

        return response()->json([
            'success'  => true,
            'customer' => AuthController::customerPayload($c),
        ]);
    }

    /**
     * PUT /api/v1/me
     * Body: { first_name, last_name, date_of_birth, email? }
     *
     * Actualiza los datos personales del cliente:
     *   - first_name       requerido (string, 1-191 chars)
     *   - last_name        requerido (string, 1-191 chars)
     *   - date_of_birth    requerido (fecha ISO YYYY-MM-DD, entre 1900 y hoy-13 años)
     *   - email            opcional (valida formato si viene)
     *
     * Ojo: el campo interno `name` (usado por UltimatePOS en toda la UI)
     * se recalcula como "first_name last_name" para mantener consistencia.
     */
    public function update(Request $request): JsonResponse
    {
        $c = $request->attributes->get('api_customer');

        $first = trim((string) $request->input('first_name', ''));
        $last  = trim((string) $request->input('last_name', ''));
        $dob   = trim((string) $request->input('date_of_birth', ''));
        $email = trim((string) $request->input('email', ''));

        // === Validaciones ===
        if ($first === '') {
            return response()->json(['success' => false, 'message' => 'El nombre es obligatorio.'], 422);
        }
        if (mb_strlen($first) > 191) {
            return response()->json(['success' => false, 'message' => 'El nombre es demasiado largo.'], 422);
        }
        if ($last === '') {
            return response()->json(['success' => false, 'message' => 'Los apellidos son obligatorios.'], 422);
        }
        if (mb_strlen($last) > 191) {
            return response()->json(['success' => false, 'message' => 'Los apellidos son demasiado largos.'], 422);
        }
        if ($dob === '') {
            return response()->json(['success' => false, 'message' => 'La fecha de nacimiento es obligatoria.'], 422);
        }

        // Validación fecha: parse estricto y rango razonable.
        try {
            $dob_carbon = \Carbon\Carbon::createFromFormat('Y-m-d', $dob);
            if (!$dob_carbon || $dob_carbon->format('Y-m-d') !== $dob) {
                throw new \Exception('formato inválido');
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fecha de nacimiento inválida. Usa formato YYYY-MM-DD.',
            ], 422);
        }

        $min_date = \Carbon\Carbon::create(1900, 1, 1);
        $max_date = \Carbon\Carbon::now()->subYears(13); // Mínimo 13 años
        if ($dob_carbon->lt($min_date)) {
            return response()->json([
                'success' => false,
                'message' => 'Fecha de nacimiento inválida.',
            ], 422);
        }
        if ($dob_carbon->gt($max_date)) {
            return response()->json([
                'success' => false,
                'message' => 'Debes tener al menos 13 años para usar la app.',
            ], 422);
        }

        // Email opcional pero si viene tiene que ser válido.
        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 191) {
                return response()->json([
                    'success' => false,
                    'message' => 'El correo electrónico no es válido.',
                ], 422);
            }
        }

        // === Actualizar ===
        $c->first_name = $first;
        $c->last_name  = $last;
        // Mantener `name` sincronizado para que UltimatePOS lo muestre bien
        // en el POS/reportes/tickets. Es el campo que usan las vistas por default.
        $c->name       = trim($first . ' ' . $last);
        $c->dob        = $dob_carbon->toDateString();
        $c->email      = $email !== '' ? $email : null;
        $c->saveQuietly();

        return response()->json([
            'success'  => true,
            'message'  => 'Datos actualizados.',
            'customer' => AuthController::customerPayload($c),
        ]);
    }
}
