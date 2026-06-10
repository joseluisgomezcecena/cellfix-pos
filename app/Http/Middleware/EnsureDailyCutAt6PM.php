<?php

namespace App\Http\Middleware;

use App\BusinessLocation;
use App\DailyCut;
use App\Utils\DailyCutUtil;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Auto-generates the daily cut once per day, after 6:00 PM.
 *
 * Works without an external cron: piggy-backs on user traffic.
 * The first authenticated request that arrives at or after 18:00 each day
 * triggers cuts for every active location of the current business — as long
 * as today's cut wasn't already generated AFTER 18:00.
 *
 * Cheap: at most a cache lookup + one COUNT query per request, and the actual
 * cut generation runs at most once per business per day.
 */
class EnsureDailyCutAt6PM
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Run after the response so the user isn't blocked waiting for cut generation.
        try {
            $this->maybeRun($request);
        } catch (\Throwable $e) {
            \Log::warning('[daily-cut-heartbeat] failed: ' . $e->getMessage());
        }

        return $response;
    }

    private function maybeRun($request)
    {
        $business_id = $request->session()->get('user.business_id');
        if (!$business_id) {
            return;
        }

        $now = Carbon::now();

        // REGLA: el corte oficial se toma al primer request después de las 18:00.
        // Una vez tomado queda CONGELADO — no se regenera al abrir reportes, ni en días
        // siguientes. Si llegan ventas después de las 18:00 cuentan para el día siguiente.
        if ($now->hour < 18) {
            return;
        }

        $today = $now->toDateString();

        // Fast check via cache so we skip the DB query most of the time.
        $doneKey = 'daily_cut_auto_done_' . $business_id . '_' . $today;
        if (Cache::has($doneKey)) {
            return;
        }

        // Mutex: prevent concurrent runs from racing on the same business/date.
        $lockKey = 'daily_cut_auto_lock_' . $business_id . '_' . $today;
        if (Cache::has($lockKey)) {
            return;
        }
        Cache::put($lockKey, 1, 120);

        try {
            $util = app(DailyCutUtil::class);

            // Procesar UBICACIÓN POR UBICACIÓN — saltar sucursales cuyo corte de hoy ya esté cerrado.
            // (Caso típico: sucursal que cerró caja manualmente a las 15:00 los sábados; el
            // heartbeat la ignora y NO sobreescribe su corte.)
            $location_ids = BusinessLocation::where('business_id', $business_id)
                ->where('is_active', 1)
                ->pluck('id');

            $closed_locations = DailyCut::where('business_id', $business_id)
                ->where('cut_date', $today)
                ->whereNotNull('closed_at')
                ->pluck('location_id')
                ->flip();

            $processed = 0;
            $skipped = 0;
            foreach ($location_ids as $loc_id) {
                if ($closed_locations->has($loc_id)) {
                    $skipped++;
                    continue; // ya cerrado manualmente, respetar
                }
                $cut = $util->upsert($business_id, $loc_id, $today, null);
                // El auto-cut de las 18:00 también CIERRA el corte para que quede congelado.
                $cut->closed_at = $now;
                $cut->closed_by = null; // null = cerrado por el sistema (heartbeat)
                $cut->save();
                $processed++;
            }

            \Log::info("[daily-cut-heartbeat] business={$business_id} date={$today} processed={$processed} skipped_already_closed={$skipped}");
            Cache::put($doneKey, 1, $now->copy()->endOfDay()->diffInSeconds($now));
        } finally {
            Cache::forget($lockKey);
        }
    }

}
