<?php

namespace App\Http\Controllers\Api\V1;

use App\AppBenefit;
use App\AppPromo;
use App\BusinessLocation;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints públicos que consume la app Flutter (Celfix Socios).
 * No requieren auth — es data que puede ver cualquier persona que abra la app.
 *
 * business_id: por default hardcoded a Celfix (2). Si algún día hay más businesses
 * hay que aceptarlo por header o query param y validar.
 */
class PublicController extends Controller
{
    private const BUSINESS_ID = 2;

    /**
     * GET /api/v1/locations
     * Lista de sucursales públicas para el tab "Tiendas" de la app.
     * Solo devuelve las que tienen is_public_in_app = 1.
     *
     * Respuesta: { success: true, data: [{ id, name, address, phone,
     *   hours: {mon: {open,close}|{closed}, ...}, latitude, longitude,
     *   maps_url }] }
     */
    public function locations(Request $request): JsonResponse
    {
        $locations = BusinessLocation::where('business_id', self::BUSINESS_ID)
            ->where('is_public_in_app', 1)
            ->orderBy('name')
            ->get();

        $data = $locations->map(function ($l) {
            $hours = is_string($l->hours_json) ? json_decode($l->hours_json, true) : ($l->hours_json ?? []);
            $lat = $l->latitude ? (float) $l->latitude : null;
            $lng = $l->longitude ? (float) $l->longitude : null;
            return [
                'id' => $l->id,
                'name' => $l->name,
                'address' => trim(implode(', ', array_filter([
                    $l->landmark, $l->city, $l->zip_code,
                ]))),
                'phone' => $l->phone_app,
                'hours' => is_array($hours) ? $hours : [],
                'latitude' => $lat,
                'longitude' => $lng,
                'maps_url' => ($lat !== null && $lng !== null)
                    ? "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}"
                    : null,
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * GET /api/v1/promos?location_id=X
     * Promos activas y vigentes hoy. Si se pasa location_id, incluye globales
     * + promos específicas de esa sucursal. Sin location_id: solo globales.
     */
    public function promos(Request $request): JsonResponse
    {
        $today = now()->toDateString();
        $location_id = $request->query('location_id');
        $location_id = is_numeric($location_id) ? (int) $location_id : null;

        $q = AppPromo::where('business_id', self::BUSINESS_ID)
            ->where('is_active', 1)
            ->where(function ($x) use ($today) {
                $x->whereNull('starts_at')->orWhere('starts_at', '<=', $today);
            })
            ->where(function ($x) use ($today) {
                $x->whereNull('ends_at')->orWhere('ends_at', '>=', $today);
            });

        if ($location_id) {
            // Globales + de esa sucursal
            $q->where(function ($x) use ($location_id) {
                $x->whereNull('target_location_id')->orWhere('target_location_id', $location_id);
            });
        } else {
            $q->whereNull('target_location_id');
        }

        $promos = $q->orderBy('sort_order')->orderByDesc('id')->get();

        $data = $promos->map(fn ($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'description' => $p->description,
            'category' => $p->category,
            'starts_at' => $p->starts_at?->toDateString(),
            'ends_at' => $p->ends_at?->toDateString(),
            'target_location_id' => $p->target_location_id,
            'image_url' => $p->image_path ? asset('storage/' . $p->image_path) : null,
        ])->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * GET /api/v1/benefits?location_id=X
     * Beneficios permanentes activos. Mismo criterio de location que promos.
     */
    public function benefits(Request $request): JsonResponse
    {
        $location_id = $request->query('location_id');
        $location_id = is_numeric($location_id) ? (int) $location_id : null;

        $q = AppBenefit::where('business_id', self::BUSINESS_ID)
            ->where('is_active', 1);

        if ($location_id) {
            $q->where(function ($x) use ($location_id) {
                $x->whereNull('target_location_id')->orWhere('target_location_id', $location_id);
            });
        } else {
            $q->whereNull('target_location_id');
        }

        $benefits = $q->orderBy('sort_order')->orderByDesc('id')->get();

        $data = $benefits->map(fn ($b) => [
            'id' => $b->id,
            'title' => $b->title,
            'description' => $b->description,
            'value_type' => $b->value_type,
            'value' => $b->value !== null ? (float) $b->value : null,
            'value_text' => $b->value_text,
            'display_value' => $b->displayValue(),
            'min_purchase' => $b->min_purchase !== null ? (float) $b->min_purchase : null,
            'conditions' => $b->conditions,
            'target_location_id' => $b->target_location_id,
        ])->values();

        return response()->json(['success' => true, 'data' => $data]);
    }
}
