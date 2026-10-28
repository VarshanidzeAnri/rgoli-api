<?php

namespace App\Policies;

use App\Models\SalaryRequest;
use App\Models\User;

class SalaryRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('salary.request') || $user->can('finance.view');
    }

    public function view(User $user, SalaryRequest $request): bool
    {
        if (! $request->project->isVisibleTo($user)) {
            return false;
        }

        return $request->user_id === $user->id || $user->can('finance.view');
    }

    public function update(User $user, SalaryRequest $request): bool
    {
        return $user->can('salary.request')
            && $request->user_id === $user->id
            && $request->isPending();
    }

    public function delete(User $user, SalaryRequest $request): bool
    {
        return $this->update($user, $request);
    }

    /**
     * Approve or reject. Nobody decides their own request (the Owner bypass still applies).
     */
    public function decide(User $user, SalaryRequest $request): bool
    {
        return $user->can('salary.approve')
            && $request->isPending()
            && $request->user_id !== $user->id
            && $request->project->isVisibleTo($user);
    }
}
