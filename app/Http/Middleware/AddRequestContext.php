<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds request id and IP to every log line written during the request,
 * so Actions don't need the Request just to log it.
 */
class AddRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id');
        $requestId = is_string($requestId) && $requestId !== '' ? mb_substr($requestId, 0, 64) : (string) Str::uuid();

        Context::add('request_id', $requestId);
        Context::add('ip', $request->ip());

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
