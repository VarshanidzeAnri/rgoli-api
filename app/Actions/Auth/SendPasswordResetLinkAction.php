<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Password;

/**
 * Sends a reset link to an active user. Silent when the email is unknown (no enumeration).
 */
class SendPasswordResetLinkAction
{
    public function handle(string $email): void
    {
        Password::sendResetLink(['email' => mb_strtolower(trim($email)), 'is_active' => true]);
    }
}
