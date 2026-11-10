<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    /**
     * Check application health status (Database and Cache).
     */
    public function check(): JsonResponse
    {
        $dbStatus = 'down';
        $cacheStatus = 'down';
        $errors = [];

        // 1. Check Database connectivity
        try {
            DB::connection()->getPdo();
            // Simple query to verify database read capability
            DB::select('SELECT 1');
            $dbStatus = 'up';
        } catch (\Throwable $e) {
            $errors['database'] = $e->getMessage();
            Log::error('Healthcheck failed: Database unreachable', [
                'event' => 'healthcheck_failure',
                'service' => 'database',
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);
        }

        // 2. Check Cache connectivity & write/read
        try {
            $key = 'healthcheck_' . uniqid();
            Cache::put($key, 'ok', 10);
            $val = Cache::get($key);
            Cache::forget($key);

            if ($val === 'ok') {
                $cacheStatus = 'up';
            } else {
                throw new \RuntimeException('Cache value mismatch');
            }
        } catch (\Throwable $e) {
            $errors['cache'] = $e->getMessage();
            Log::error('Healthcheck failed: Cache operation failed', [
                'event' => 'healthcheck_failure',
                'service' => 'cache',
                'error_message' => $e->getMessage(),
            ]);
        }

        $healthy = ($dbStatus === 'up' && $cacheStatus === 'up');

        $responsePayload = [
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $dbStatus,
                'cache' => $cacheStatus,
            ],
        ];

        if (! $healthy) {
            $responsePayload['errors'] = $errors;

            return response()->json($responsePayload, 503);
        }

        return response()->json($responsePayload, 200);
    }
}
