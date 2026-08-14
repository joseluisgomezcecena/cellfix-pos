<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Conteo manual de billetes que el cajero captura al cierre del día — separado del
 * cálculo automático del sistema en daily_cuts.summary. Se muestra debajo de cada
 * fila del reporte de denominaciones para comparar.
 *
 * Único por (business_id, location_id, cut_date). Upsert al guardar.
 */
class DailyCutVendorCount extends Model
{
    protected $fillable = [
        'business_id', 'location_id', 'cut_date',
        'mxn_counts', 'mxn_coins',
        'usd_counts', 'usd_coins', 'usd_exchange_rate',
        'note', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'cut_date' => 'date',
        'mxn_counts' => 'array',
        'usd_counts' => 'array',
        'mxn_coins' => 'decimal:2',
        'usd_coins' => 'decimal:2',
        'usd_exchange_rate' => 'decimal:4',
    ];

    /**
     * Suma total en MXN: billetes MXN + monedas MXN + billetes/monedas USD × exchange_rate.
     */
    public function totalInMxn(): float
    {
        $sum = (float) $this->mxn_coins;
        foreach ($this->mxn_counts ?? [] as $face => $count) {
            if (is_numeric($face)) $sum += (int) $face * (int) $count;
        }
        $usd_sum = (float) $this->usd_coins;
        foreach ($this->usd_counts ?? [] as $face => $count) {
            if (is_numeric($face)) $usd_sum += (int) $face * (int) $count;
        }
        $rate = (float) ($this->usd_exchange_rate ?? 0);
        return $sum + ($rate > 0 ? $usd_sum * $rate : 0);
    }
}
