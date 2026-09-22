<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthCheckController extends Controller
{
    /**
     * Check application dependencies and operational health.
     */
    public function __invoke(): JsonResponse
    {
        $status = 'healthy';
        $httpCode = 200;

        // 1. Check Database
        try {
            DB::connection()->getPdo();
            $dbStatus = 'connected';
        } catch (Throwable $e) {
            Log::error('Health check database failure: ' . $e->getMessage());
            $dbStatus = 'unreachable';
            $status = 'unhealthy';
            $httpCode = 503;
        }

        // 2. Check Cache
        try {
            $key = '_health_ping_' . uniqid();
            Cache::put($key, true, 5);
            $cacheCheck = Cache::get($key);
            Cache::forget($key);
            $cacheStatus = $cacheCheck ? 'connected' : 'failed';
            if ($cacheStatus !== 'connected') {
                $status = 'unhealthy';
                $httpCode = 503;
            }
        } catch (Throwable $e) {
            Log::error('Health check cache failure: ' . $e->getMessage());
            $cacheStatus = 'unreachable';
            $status = 'unhealthy';
            $httpCode = 503;
        }

        // 3. Check Storage
        try {
            $testFile = '_health_' . uniqid() . '.txt';
            Storage::disk('public')->put($testFile, 'ok');
            $storageWritable = Storage::disk('public')->exists($testFile);
            Storage::disk('public')->delete($testFile);
            $storageStatus = $storageWritable ? 'writable' : 'readonly';
            if ($storageStatus !== 'writable') {
                $status = 'unhealthy';
                $httpCode = 503;
            }
        } catch (Throwable $e) {
            Log::error('Health check storage failure: ' . $e->getMessage());
            $storageStatus = 'unwritable';
            $status = 'unhealthy';
            $httpCode = 503;
        }

        return response()->json([
            'status' => $status,
            'database' => $dbStatus,
            'cache' => $cacheStatus,
            'storage' => $storageStatus,
            'environment' => config('app.env'),
            'timestamp' => now()->toIso8601String(),
        ], $httpCode, [
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
