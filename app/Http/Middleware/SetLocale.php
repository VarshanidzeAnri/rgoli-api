<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The UI language lives only in the browser (localStorage); the SPA sends it on every
 * request as Accept-Language. Resolution: Accept-Language → ka.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolve($request));

        $response = $next($request);
        $response->headers->set('Content-Language', app()->getLocale());

        return $response;
    }

    private function resolve(Request $request): string
    {
        $supported = Locale::values();
        $header = $request->header('Accept-Language');

        if (is_string($header) && $header !== '') {
            foreach (explode(',', $header) as $part) {
                $code = strtolower(substr(trim(explode(';', $part)[0]), 0, 2));
                if (in_array($code, $supported, true)) {
                    return $code;
                }
            }
        }

        return Locale::Ka->value;
    }
}
