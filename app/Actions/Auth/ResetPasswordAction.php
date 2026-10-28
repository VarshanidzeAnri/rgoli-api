<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Resets the password with a broker token and revokes every existing API token.
 */
class ResetPasswordAction
{
    public function __construct(private UserRepositoryInterface $users) {}

    /**
     * @param  array{email: string, token: string, password: string, password_confirmation: string}  $data
     */
    public function handle(array $data): void
    {
        $status = Password::reset($data, function (User $user, string $password): void {
            $this->users->update($user, ['password' => $password]);
            $this->users->revokeTokens($user);
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __((string) $status)]);
        }
    }
}
