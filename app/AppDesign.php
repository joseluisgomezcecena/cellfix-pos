<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Diseños visuales de la app Celfix Socios (Flutter).
 * Cada fila representa un elemento gráfico configurable desde el admin del POS
 * (bajo App Config → Diseños). Ejemplos:
 *
 *   design_key                       uso en la app
 *   ─────────────────────────────    ────────────────────────────────────────
 *   membership_card_background       Fondo de la tarjeta de membresía
 *   (futuros: app_logo, splash, ...)
 *
 * Único por (business_id, design_key). La app los consume por
 * GET /api/v1/app-designs (endpoint público).
 */
class AppDesign extends Model
{
    protected $table = 'app_designs';

    protected $fillable = [
        'business_id', 'design_key', 'image_path', 'metadata',
        'updated_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /** Claves de diseño soportadas (con specs) */
    public const KEYS = [
        'membership_card_background' => [
            'label' => 'Fondo de tarjeta de membresía',
            'description' => 'Imagen de fondo que se muestra detrás del QR y datos del socio.',
            'recommended_size' => '1600 × 1000 px',
            'aspect_ratio' => '16:10',
            'max_bytes' => 5 * 1024 * 1024, // 5 MB
        ],
        // Futuros diseños se agregan aquí sin migraciones adicionales.
    ];

    /**
     * URL pública absoluta de la imagen (respeta APP_URL para HTTPS/proxy).
     */
    public function imageUrl(): ?string
    {
        if (empty($this->image_path)) return null;
        return asset('storage/' . $this->image_path);
    }
}
