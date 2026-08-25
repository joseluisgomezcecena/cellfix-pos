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
        // Captura manual del cajero para los otros métodos de pago,
        // para poder "empatar" cuando hay diferencia con el sistema.
        'terminals_manual', 'transfer_manual', 'cheque_manual',
        'note', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'cut_date' => 'date',
        'mxn_counts' => 'array',
        'usd_counts' => 'array',
        'terminals_manual' => 'array',
        'mxn_coins' => 'decimal:2',
        'usd_coins' => 'decimal:2',
        'usd_exchange_rate' => 'decimal:4',
        'transfer_manual' => 'decimal:2',
        'cheque_manual' => 'decimal:2',
    ];

    /**
     * Suma total en MXN de solo el efectivo (billetes MXN + monedas + USD × rate).
     * Se usa para la fila "Efectivo por el vendedor" cuando queremos comparar
     * SOLO efectivo contra efectivo del sistema.
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

    /**
     * Suma de todo lo capturado manualmente por el cajero: efectivo + terminales
     * + transfer + cheque. Es el "DINERO POR EL VENDEDOR" del reporte semanal, y
     * se compara contra el TOTAL DINERO del sistema para sacar la diferencia final.
     */
    public function totalManualMxn(): float
    {
        $sum = $this->totalInMxn();
        foreach (($this->terminals_manual ?? []) as $bank => $amount) {
            $sum += (float) $amount;
        }
        $sum += (float) ($this->transfer_manual ?? 0);
        $sum += (float) ($this->cheque_manual ?? 0);
        return $sum;
    }

    /**
     * Suma solo de terminales manuales del cajero (para separarlo del efectivo
     * al desplegar en el reporte semanal).
     */
    public function terminalsManualSum(): float
    {
        $s = 0;
        foreach (($this->terminals_manual ?? []) as $bank => $amount) {
            $s += (float) $amount;
        }
        return $s;
    }
}
