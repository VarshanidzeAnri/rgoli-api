<?php

namespace App\Repositories;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

/**
 * @extends BaseRepository<Role>
 */
class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    protected function model(): string
    {
        return Role::class;
    }

    public function allWithPermissions(): Collection
    {
        return $this->query()
            ->with('permissions')
            ->withCount('users')
            ->orderByDesc('is_system')
            ->orderBy('id')
            ->get();
    }

    public function findWithPermissions(int $id): Role
    {
        return $this->query()->with('permissions')->withCount('users')->findOrFail($id);
    }

    public function create(array $attributes): Role
    {
        return $this->query()->create([...$attributes, 'guard_name' => 'web']);
    }

    public function syncPermissions(Role $role, array $permissions): void
    {
        $role->syncPermissions($permissions);
        $this->flushPermissionCache();
    }

    public function hasUsers(Role $role): bool
    {
        return $role->users()->exists();
    }

    public function flushPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
