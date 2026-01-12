<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    /**
     * Health check endpoint
     *
     * @return JsonResponse
     */
    public function check(): JsonResponse
    {
        try {
            // Test database connection
            DB::connection()->getPdo();
            
            return response()->json([
                'status' => 'ok',
                'app' => config('app.name'),
                'environment' => config('app.env'),
                'database' => 'connected',
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Health check failed', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'database' => 'disconnected',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

