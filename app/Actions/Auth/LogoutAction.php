<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Revokes the token used for this request, or every token when $everywhere is true.
 */
class LogoutAction
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function handle(User $user, bool $everywhere = false): void
    {
        if ($everywhere) {
            $this->users->revokeTokens($user);
        } else {
            $token = $user->currentAccessToken();
            if ($token instanceof PersonalAccessToken) {
                $token->delete();
            }
        }

        Log::channel('auth')->info('logout', ['user_id' => $user->id, 'everywhere' => $everywhere]);
    }
}
