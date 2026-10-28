<?php

namespace App\Actions\Profile;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UpdateProfileAction
{
    public function __construct(private UserRepositoryInterface $users) {}

    /**
     * @param  array{name?: string, job_title?: string|null}  $data
     */
    public function handle(User $user, array $data): User
    {
        return $this->users->update($user, $data);
    }
}
