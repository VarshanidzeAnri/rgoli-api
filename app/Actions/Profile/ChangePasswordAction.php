<?php

namespace App\Actions\Profile;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Changes the password and signs out every other device.
 */
class ChangePasswordAction
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function handle(User $user, string $password): void
    {
        $this->users->update($user, ['password' => $password]);

        $current = $user->currentAccessToken();
        $user->tokens()
            ->when($current instanceof PersonalAccessToken, fn ($q) => $q->whereKeyNot($current->getKey()))
            ->delete();

        Log::channel('auth')->info('password.changed', ['user_id' => $user->id]);
    }
}
