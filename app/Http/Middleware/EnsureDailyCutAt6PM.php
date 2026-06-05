<?php

namespace App\Http\Middleware;

use App\DailyCut;
use App\Utils\DailyCutUtil;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Cache;

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

        // Finalización de AYER (cualquier hora del día):
        // Si el corte de ayer fue generado antes de medianoche (típicamente la foto de 6 PM),
        // regenerarlo una sola vez para capturar ventas tardías 6:01 PM → 23:59 PM.
        $this->maybeFinalizeYesterday($business_id, $now);

        // Auto-cut de HOY: solo se dispara después de las 18:00.
        if ($now->hour < 18) {
            return;
        }

        $today = $now->toDateString();
        $cutoff = $now->copy()->startOfDay()->setTime(18, 0); // today 18:00:00

        // Fast check via cache so we skip the DB query most of the time.
        $doneKey = 'daily_cut_auto_done_' . $business_id . '_' . $today;
        if (Cache::has($doneKey)) {
            return;
        }

        // Already finalized today (any location, generated at >= 18:00)? Mark cache and skip.
        $hasFresh = DailyCut::where('business_id', $business_id)
            ->where('cut_date', $today)
            ->where('generated_at', '>=', $cutoff)
            ->exists();
        if ($hasFresh) {
            Cache::put($doneKey, 1, $now->copy()->endOfDay()->diffInSeconds($now));
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
            $util->generateForBusiness($business_id, $today, null);
            \Log::info("[daily-cut-heartbeat] auto-generated cuts business={$business_id} date={$today}");
            Cache::put($doneKey, 1, $now->copy()->endOfDay()->diffInSeconds($now));
        } finally {
            Cache::forget($lockKey);
        }
    }

    /**
     * Regenera el corte de AYER una sola vez al día si fue generado antes de medianoche
     * (la foto de las 6 PM no incluye ventas posteriores hasta 23:59).
     */
    private function maybeFinalizeYesterday($business_id, $now)
    {
        $yesterday = $now->copy()->subDay()->toDateString();
        $today_midnight = $now->copy()->startOfDay();

        // Cache: una sola finalización por business × día.
        $finalKey = 'daily_cut_finalize_yesterday_' . $business_id . '_' . $yesterday;
        if (Cache::has($finalKey)) {
            return;
        }

        // ¿Algún corte de ayer está aún con la foto vieja (anterior a hoy 00:00)?
        $needsFinalize = DailyCut::where('business_id', $business_id)
            ->where('cut_date', $yesterday)
            ->where('generated_at', '<', $today_midnight)
            ->exists();

        if (!$needsFinalize) {
            Cache::put($finalKey, 1, $now->copy()->endOfDay()->diffInSeconds($now));
            return;
        }

        $lockKey = 'daily_cut_finalize_yesterday_lock_' . $business_id . '_' . $yesterday;
        if (Cache::has($lockKey)) {
            return;
        }
        Cache::put($lockKey, 1, 120);

        try {
            $util = app(DailyCutUtil::class);
            $util->generateForBusiness($business_id, $yesterday, null);
            \Log::info("[daily-cut-heartbeat] finalize yesterday business={$business_id} date={$yesterday}");
            Cache::put($finalKey, 1, $now->copy()->endOfDay()->diffInSeconds($now));
        } finally {
            Cache::forget($lockKey);
        }
    }
}
