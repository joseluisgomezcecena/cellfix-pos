<?php

namespace App\Services\WhatsApp;

/**
 * Servicio de envío de mensajes por WhatsApp.
 *
 * Interfaz simple para desacoplar el envío del proveedor real. Hoy soporta:
 *   - stub → solo loguea en storage/logs/laravel.log (default, para dev)
 *   - meta → Meta WhatsApp Business Cloud API (producción)
 *
 * Se configura vía .env:
 *   WHATSAPP_PROVIDER=stub|meta
 *   META_WA_PHONE_ID=...
 *   META_WA_ACCESS_TOKEN=...
 *   META_WA_TEMPLATE_NAME=... (opcional, si Meta requiere template)
 *
 * Uso:
 *   $ok = app(WhatsAppService::class)->sendPasswordReset('6861234567', 'Xk4T92aq');
 */
class WhatsAppService
{
    /**
     * Envía la contraseña temporal al número dado.
     * Devuelve true si el envío se despachó OK, false si falló.
     * Nunca lanza excepción hacia arriba — el fallo se registra en log.
     */
    public function sendPasswordReset(string $mobile, string $temp_password): bool
    {
        $provider = config('services.whatsapp.provider', 'stub');
        $mobile = $this->normalizeMobile($mobile);

        try {
            if ($provider === 'meta') {
                return $this->sendViaMeta($mobile, $temp_password);
            }
            return $this->sendViaStub($mobile, $temp_password);
        } catch (\Throwable $e) {
            \Log::error('[whatsapp] envío falló: ' . $e->getMessage(), [
                'mobile' => $mobile,
                'provider' => $provider,
            ]);
            return false;
        }
    }

    /**
     * Stub: solo loguea. Útil para desarrollo y mientras Meta está en approval.
     * Con esto el flow completo funciona; solo hay que revisar el log para ver
     * qué se "envió".
     */
    private function sendViaStub(string $mobile, string $temp_password): bool
    {
        \Log::info(sprintf(
            '[whatsapp:stub] SIMULADO — enviar a %s: "Tu contraseña temporal de Celfix Socios es: %s. Ingresa a la app y cámbiala."',
            $mobile,
            $temp_password
        ));
        return true;
    }

    /**
     * Meta WhatsApp Business Cloud API.
     * Docs: https://developers.facebook.com/docs/whatsapp/cloud-api/
     *
     * Meta obliga a usar TEMPLATE aprobado para mensajes iniciados por el negocio.
     * El template debe estar aprobado en Meta Business Manager y tener 1 parámetro
     * de tipo texto (donde va la contraseña temporal).
     *
     * Formato del número: E.164 con lada país sin '+'. Para México: 521XXXXXXXXXX
     * (521 = 52 país + 1 histórico requerido por WA para móviles mexicanos).
     */
    private function sendViaMeta(string $mobile, string $temp_password): bool
    {
        $phone_id = config('services.whatsapp.meta.phone_id');
        $token = config('services.whatsapp.meta.access_token');
        $template = config('services.whatsapp.meta.template_name', 'celfix_password_reset');
        $lang = config('services.whatsapp.meta.template_lang', 'es_MX');

        if (empty($phone_id) || empty($token)) {
            \Log::error('[whatsapp:meta] credenciales incompletas (META_WA_PHONE_ID o META_WA_ACCESS_TOKEN)');
            return false;
        }

        // México celular: prepend '521' si viene con 10 dígitos
        $mx_mobile = strlen($mobile) === 10 ? '521' . $mobile : $mobile;

        $url = "https://graph.facebook.com/v20.0/{$phone_id}/messages";
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $mx_mobile,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => $lang],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $temp_password],
                        ],
                    ],
                ],
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
        ]);
        $resp = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code >= 200 && $http_code < 300) {
            \Log::info("[whatsapp:meta] enviado a {$mx_mobile} (HTTP {$http_code})");
            return true;
        }

        \Log::error("[whatsapp:meta] fallo HTTP {$http_code} enviando a {$mx_mobile}", [
            'response' => $resp,
            'curl_error' => $curl_error,
        ]);
        return false;
    }

    /** Normaliza a solo dígitos, últimos 10 si vino con lada. */
    private function normalizeMobile(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';
        return substr($digits, -10);
    }
}
