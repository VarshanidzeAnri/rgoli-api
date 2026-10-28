<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

/**
 * Admin-triggered password reset email.
 */
class SendUserPasswordResetAction
{
    public function handle(User $actor, User $user): void
    {
        Password::sendResetLink(['email' => $user->email]);
        Log::channel('auth')->info('user.reset_link_sent', ['user_id' => $user->id, 'by' => $actor->id]);
    }
}
