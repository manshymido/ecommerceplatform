<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures every request has a correlation/request ID for tracing and log correlation.
 * Uses X-Request-ID header if present, otherwise generates a UUID.
 * Adds request_id to log context for the duration of the request.
 */
class RequestId
{
    public const HEADER_NAME = 'X-Request-ID';

    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->header(self::HEADER_NAME) ?: Str::uuid()->toString();
        $request->attributes->set('request_id', $id);
        Log::withContext(['request_id' => $id]);

        $response = $next($request);
        $response->headers->set(self::HEADER_NAME, $id);

        return $response;
    }
}
