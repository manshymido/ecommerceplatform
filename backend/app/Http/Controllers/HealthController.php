<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Readiness probe: checks that the app can serve traffic (e.g. DB reachable).
 * Use for load balancer / Kubernetes readiness checks.
 */
class HealthController extends Controller
{
    public function ready(): JsonResponse
    {
        $checks = ['status' => 'ok'];

        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'error';
            return response()->json($checks, 503);
        }

        return response()->json($checks);
    }
}
