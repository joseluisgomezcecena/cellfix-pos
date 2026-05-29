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
        // Only fires from 6 PM onwards.
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
}
