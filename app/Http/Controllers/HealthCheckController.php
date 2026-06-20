<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $allOk = true;

        // Database
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            $checks['database'] = 'fail';
            $allOk = false;
        }

        // Redis
        try {
            Cache::store('redis')->put('health_check', 1, 10);
            $checks['redis'] = 'ok';
        } catch (\Throwable) {
            $checks['redis'] = 'fail';
            $allOk = false;
        }

        // Queue heartbeat: verificato tramite HealthCheckJob schedulato ogni minuto
        $heartbeat = Cache::get('health_check_heartbeat');
        if ($heartbeat !== null && now()->diffInMinutes(Carbon::parse($heartbeat)) < 5) {
            $checks['queue'] = 'ok';
        } else {
            $checks['queue'] = 'fail';
            $allOk = false;
        }

        // Disk (>= 500 MB liberi)
        $freeBytes = disk_free_space(storage_path());
        if ($freeBytes !== false && $freeBytes > 500 * 1024 * 1024) {
            $checks['disk'] = 'ok';
        } else {
            $checks['disk'] = 'fail';
            $allOk = false;
        }

        return response()->json([
            'status' => $allOk ? 'ok' : 'fail',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $allOk ? 200 : 503);
    }
}
