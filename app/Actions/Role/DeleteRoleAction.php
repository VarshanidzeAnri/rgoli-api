<?php

namespace App\Actions\Role;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Deletes a custom role. Roles still assigned to users can't be deleted.
 */
class DeleteRoleAction
{
    public function __construct(private RoleRepositoryInterface $roles) {}

    public function handle(User $actor, Role $role): void
    {
        if ($this->roles->hasUsers($role)) {
            throw ValidationException::withMessages(['role' => __('messages.role_in_use')]);
        }

        $this->roles->delete($role);
        $this->roles->flushPermissionCache();
        Log::channel('auth')->info('role.deleted', ['role_id' => $role->id, 'by' => $actor->id]);
    }
}
