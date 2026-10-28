<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;

/**
 * Every route behind auth:sanctum answers 401 (translated JSON) without a token.
 */
it('requires a token on every protected route', function (): void {
    seedDemo();

    $protected = collect(Router::getRoutes()->getRoutes())
        ->filter(fn (Route $route): bool => str_starts_with($route->uri(), 'api/v1/')
            && in_array('auth:sanctum', $route->gatherMiddleware(), true));

    expect($protected->count())->toBeGreaterThan(60);

    foreach ($protected as $route) {
        $method = collect($route->methods())->reject(fn (string $m): bool => $m === 'HEAD')->first();
        $uri = '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri());

        $response = $this->withHeader('Accept-Language', 'en')->json((string) $method, $uri);

        expect($response->getStatusCode())->toBe(401, "{$method} {$uri} should require auth")
            ->and($response->json('message'))->toBe('Please sign in.');
    }
});
