<?php

namespace App\Policies;

use App\Models\ClientPayment;
use App\Models\User;

class ClientPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance.view');
    }

    public function update(User $user, ClientPayment $payment): bool
    {
        return $user->can('client_payments.manage') && $payment->project->isVisibleTo($user);
    }

    public function delete(User $user, ClientPayment $payment): bool
    {
        return $this->update($user, $payment);
    }
}
