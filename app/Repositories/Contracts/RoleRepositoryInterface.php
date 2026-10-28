<?php

namespace App\Repositories\Contracts;

use App\Models\Role;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepositoryInterface<Role>
 */
interface RoleRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<int, Role>
     */
    public function allWithPermissions(): Collection;

    public function findWithPermissions(int $id): Role;

    /**
     * @param  list<string>  $permissions
     */
    public function syncPermissions(Role $role, array $permissions): void;

    public function hasUsers(Role $role): bool;

    public function flushPermissionCache(): void;
}
