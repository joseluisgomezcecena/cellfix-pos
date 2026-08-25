<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Reparación de tienda (módulo REPARACION DE TIENDA).
 *
 * Registro independiente de ventas y garantías: es un servicio gratis para el
 * cliente (no se le cobra) donde el técnico repara un equipo y comisiona.
 * Vive completamente aislado del resto del sistema — no crea transactions y
 * NO aparece en cortes, dashboard de ventas, reporte semanal ni denominaciones.
 * Sí se integra con el reporte de técnicos (columna aparte "Reparación de tienda").
 */
class StoreRepair extends Model
{
    protected $table = 'store_repairs';

    protected $fillable = [
        'business_id', 'location_id',
        'imei', 'technician_id', 'commission',
        'customer_name', 'customer_mobile',
        'notes', 'status', 'delivered_at',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'commission' => 'decimal:2',
        'delivered_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'Pendiente',
        'in_progress' => 'En proceso',
        'delivered' => 'Entregada',
        'cancelled' => 'Cancelada',
    ];

    public static function statusLabel(?string $status): string
    {
        return self::STATUSES[$status] ?? '—';
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function technician()
    {
        return $this->belongsTo(\App\Technician::class, 'technician_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
