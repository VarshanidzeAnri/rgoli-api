<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\MeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group Auth
 *
 * Sanctum personal access tokens. Send `Authorization: Bearer <token>` on every request.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Log in and receive a token.
     *
     * Rate-limited to 5 attempts per minute per email + IP.
     *
     * @unauthenticated
     *
     * @response 200 {"data": {"token": "1|abc...", "token_type": "Bearer", "user": {}, "roles": [], "permissions": [], "disciplines": []}}
     * @response 422 {"message": "...", "errors": {"email": ["..."]}}
     */
    public function store(LoginRequest $request, LoginAction $login): JsonResponse
    {
        ['token' => $token, 'user' => $user] = $login->handle(
            (string) $request->validated('email'),
            (string) $request->validated('password'),
        );

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                ...(new MeResource($user))->resolve($request),
            ],
        ]);
    }

    /**
     * Log out (revokes the current token; `?everywhere=1` revokes all of them).
     */
    public function destroy(Request $request, LogoutAction $logout): Response
    {
        $logout->handle($this->actor($request), $request->boolean('everywhere'));

        return response()->noContent();
    }
}
