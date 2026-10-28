<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Verifies credentials and issues a Sanctum personal access token.
 */
class LoginAction
{
    public const TOKEN_NAME = 'spa';

    public function __construct(private UserRepositoryInterface $users) {}

    /**
     * @return array{token: string, user: User}
     *
     * @throws ValidationException
     */
    public function handle(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);

        // One message for "no such user" and "wrong password" so accounts can't be enumerated.
        if ($user === null || ! Hash::check($password, $user->getAuthPassword())) {
            Log::channel('auth')->warning('login.failed', ['email' => $email]);

            throw ValidationException::withMessages(['email' => __('messages.wrong_credentials')]);
        }

        if (! $user->is_active) {
            Log::channel('auth')->warning('login.inactive', ['user_id' => $user->id]);

            throw ValidationException::withMessages(['email' => __('messages.inactive')]);
        }

        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;
        Log::channel('auth')->info('login.success', ['user_id' => $user->id]);

        return ['token' => $token, 'user' => $this->users->loadAccess($user)];
    }
}
