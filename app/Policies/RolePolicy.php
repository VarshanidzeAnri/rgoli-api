<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.manage') || $user->can('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.manage');
    }

    /**
     * System (seeded) roles can never be deleted, not even by the Owner.
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.manage') && ! $role->is_system;
    }
}
