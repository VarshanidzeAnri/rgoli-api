<?php

namespace App\Repositories\Contracts;

use App\DTOs\Filters\UserFilters;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepositoryInterface<User>
 */
interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    /**
     * User with roles, direct permissions and disciplines loaded (for /me and admin).
     */
    public function loadAccess(User $user): User;

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(UserFilters $filters): LengthAwarePaginator;

    /**
     * Active users for assignee / member pickers.
     *
     * @return Collection<int, User>
     */
    public function options(): Collection;

    /**
     * @param  list<int>  $ids
     * @return Collection<int, User>
     */
    public function findMany(array $ids): Collection;

    public function assignRole(User $user, Role $role): void;

    /**
     * @param  list<string>  $permissions
     */
    public function syncDirectPermissions(User $user, array $permissions): void;

    /**
     * @param  list<string>  $disciplines
     */
    public function syncDisciplines(User $user, array $disciplines): void;

    /**
     * Active users holding a permission (through a role or directly), Owner included.
     *
     * @return Collection<int, User>
     */
    public function withPermission(string $permission, ?int $exceptUserId = null): Collection;

    public function activeCount(): int;

    public function revokeTokens(User $user): void;
}
