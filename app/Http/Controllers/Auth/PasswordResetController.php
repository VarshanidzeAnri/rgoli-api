<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResetPasswordAction;
use App\Actions\Auth\SendPasswordResetLinkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

/**
 * @group Auth
 */
class PasswordResetController extends Controller
{
    /**
     * Email a reset link. Always 200 so it can't reveal which emails exist.
     *
     * @unauthenticated
     */
    public function store(ForgotPasswordRequest $request, SendPasswordResetLinkAction $action): JsonResponse
    {
        $action->handle((string) $request->validated('email'));

        return $this->message('passwords.sent');
    }

    /**
     * Set a new password with the emailed token. Revokes all existing tokens.
     *
     * @unauthenticated
     */
    public function update(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        /** @var array{email: string, token: string, password: string, password_confirmation: string} $data */
        $data = $request->only('email', 'token', 'password', 'password_confirmation');
        $action->handle($data);

        return $this->message('passwords.reset');
    }
}
