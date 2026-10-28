<?php

namespace App\Actions\Role;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Creates or renames a role and sets its permissions. The Owner role always keeps every permission.
 */
class SaveRoleAction
{
    public function __construct(private RoleRepositoryInterface $roles) {}

    /**
     * @param  array{name?: string, permissions?: list<string>}  $data
     */
    public function handle(User $actor, array $data, ?Role $role = null): Role
    {
        $role = DB::transaction(function () use ($data, $role): Role {
            if ($role === null) {
                $role = $this->roles->create(['name' => $data['name'] ?? '']);
            } elseif (isset($data['name'])) {
                $this->roles->update($role, ['name' => $data['name']]);
            }

            if ($role->slug === PermissionCatalog::OWNER) {
                $this->roles->syncPermissions($role, PermissionCatalog::all());
            } elseif (array_key_exists('permissions', $data)) {
                $this->roles->syncPermissions($role, $data['permissions']);
            }

            return $role;
        });

        Log::channel('auth')->info('role.saved', ['role_id' => $role->id, 'by' => $actor->id]);

        return $this->roles->findWithPermissions($role->id);
    }
}
