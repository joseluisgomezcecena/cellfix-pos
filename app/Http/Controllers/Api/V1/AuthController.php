<?php

namespace App\Http\Controllers\Api\V1;

use App\Contact;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Auth de clientes de la app Celfix Socios (Flutter).
 * Login con teléfono + password. Devuelve token bearer que se guarda en
 * contacts.app_api_token y se manda en Authorization: Bearer <token>
 * en cada request siguiente.
 *
 * Deliberadamente no expone si el mobile existe o no — todas las respuestas
 * fallidas dicen lo mismo para no ayudar a enumerar clientes.
 */
class AuthController extends Controller
{
    private const BUSINESS_ID = 2;

    /**
     * POST /api/v1/auth/login
     * Body: { mobile: "6861234567", password: "password1" }
     * OK  : { success:true, token:"xxx", customer:{...} }
     * Fail: { success:false, message:"Credenciales inválidas" }
     */
    public function login(Request $request): JsonResponse
    {
        $mobile_raw = (string) $request->input('mobile', '');
        $password   = (string) $request->input('password', '');
        $mobile     = self::normalizeMobile($mobile_raw);

        if ($mobile === '' || $password === '') {
            return $this->fail();
        }

        $contact = Contact::where('business_id', self::BUSINESS_ID)
            ->whereIn('type', ['customer', 'both'])
            ->where(function ($q) use ($mobile) {
                // Compara los últimos 10 dígitos del mobile guardado.
                // Cubre "+52 686 …", "686 …", "6861234567" y variaciones con guiones.
                $q->where('mobile', $mobile)
                  ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(mobile,' ',''),'-',''),'(',''),')',''),'+','') LIKE ?", ['%' . $mobile])
                  ->orWhere('alternate_number', $mobile);
            })
            ->first();

        if (!$contact || empty($contact->app_password)) {
            return $this->fail();
        }

        if (!Hash::check($password, $contact->app_password)) {
            return $this->fail();
        }

        // Genera token bearer nuevo. Si ya había uno guardado, se sobreescribe —
        // login desde un dispositivo nuevo invalida sesiones viejas (comportamiento
        // simple, adecuado para app single-device por cliente).
        $token = Str::random(60);
        $contact->app_api_token = hash('sha256', $token);
        $contact->saveQuietly();

        return response()->json([
            'success' => true,
            'token'   => $token,
            'customer' => $this->customerPayload($contact),
        ]);
    }

    /**
     * POST /api/v1/auth/logout  (protected)
     * Invalida el token actual borrándolo de la BD.
     */
    public function logout(Request $request): JsonResponse
    {
        $contact = $request->attributes->get('api_customer');
        if ($contact) {
            $contact->app_api_token = null;
            $contact->saveQuietly();
        }
        return response()->json(['success' => true, 'message' => 'Sesión cerrada.']);
    }

    /**
     * POST /api/v1/auth/change-password  (protected)
     * Body: { current_password, new_password, new_password_confirmation }
     *
     * Al cambiar exitosamente NO invalida el token — el cliente sigue
     * autenticado en su sesión actual. Sí invalida sesiones de otros
     * dispositivos si los hubiera (regenera el token).
     */
    public function changePassword(Request $request): JsonResponse
    {
        $contact = $request->attributes->get('api_customer');
        $current = (string) $request->input('current_password', '');
        $new     = (string) $request->input('new_password', '');
        $confirm = (string) $request->input('new_password_confirmation', '');

        if ($current === '' || $new === '' || $confirm === '') {
            return response()->json(['success' => false, 'message' => 'Faltan campos.'], 422);
        }
        if (strlen($new) < 6) {
            return response()->json(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres.'], 422);
        }
        if ($new !== $confirm) {
            return response()->json(['success' => false, 'message' => 'La confirmación no coincide.'], 422);
        }
        if (!Hash::check($current, $contact->app_password ?? '')) {
            return response()->json(['success' => false, 'message' => 'La contraseña actual es incorrecta.'], 401);
        }
        if ($new === $current) {
            return response()->json(['success' => false, 'message' => 'La nueva contraseña debe ser distinta a la actual.'], 422);
        }

        // Actualiza password + rota token (invalida otros dispositivos).
        $contact->app_password = Hash::make($new);
        $new_token = Str::random(60);
        $contact->app_api_token = hash('sha256', $new_token);
        $contact->saveQuietly();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada.',
            'token'   => $new_token,
        ]);
    }

    private function fail(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Credenciales inválidas.',
        ], 401);
    }

    /**
     * Deja el mobile como los últimos 10 dígitos numéricos.
     * "+52 (686) 123-4567" → "6861234567"
     * "686 123 4567"       → "6861234567"
     */
    public static function normalizeMobile(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';
        return substr($digits, -10);
    }

    /**
     * Formato público del cliente para responder al frontend.
     * No incluye password ni token — solo lo que la app necesita mostrar.
     */
    public static function customerPayload(Contact $c): array
    {
        return [
            'id'                     => $c->id,
            'name'                   => trim(($c->name ?? '') ?: (($c->first_name ?? '') . ' ' . ($c->last_name ?? ''))),
            'mobile'                 => $c->mobile,
            'email'                  => $c->email,
            'membership_no'          => $c->membership_no,
            'membership_expires_at'  => $c->membership_expires_at,
        ];
    }
}
