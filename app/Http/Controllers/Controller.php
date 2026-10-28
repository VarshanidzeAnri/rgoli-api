<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

abstract class Controller
{
    use AuthorizesRequests;

    protected function actor(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            throw new LogicException('Authenticated route without a user.');
        }

        return $user;
    }

    /**
     * Success response without a resource, e.g. after delete: { data: null, meta: { message } }.
     */
    protected function message(string $key, int $status = 200): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['message' => __($key)]], $status);
    }

    protected function perPage(Request $request, int $default = 25): int
    {
        return max(1, min(100, (int) $request->integer('per_page', $default)));
    }
}
