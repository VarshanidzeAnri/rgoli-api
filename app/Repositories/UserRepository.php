<?php

namespace App\Repositories;

use App\DTOs\Filters\UserFilters;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\PermissionCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepository<User>
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /** @var list<string> */
    private const ACCESS = ['roles', 'permissions', 'disciplineRows'];

    protected function model(): string
    {
        return User::class;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->query()->where('email', mb_strtolower(trim($email)))->first();
    }

    public function loadAccess(User $user): User
    {
        return $user->load(self::ACCESS);
    }

    public function paginate(UserFilters $filters): LengthAwarePaginator
    {
        return $this->query()
            ->with(self::ACCESS)
            ->when($filters->search !== null && $filters->search !== '', function (Builder $q) use ($filters): void {
                $like = $this->like((string) $filters->search);
                $q->where(fn (Builder $w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($filters->roleId !== null, fn (Builder $q) => $q->whereHas('roles', fn (Builder $r) => $r->whereKey($filters->roleId)))
            ->when($filters->active !== null, fn (Builder $q) => $q->where('is_active', $filters->active))
            ->orderBy('name')
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    public function options(): Collection
    {
        return $this->query()->active()->orderBy('name')->get();
    }

    public function findMany(array $ids): Collection
    {
        return $this->query()->whereKey($ids)->get();
    }

    public function assignRole(User $user, Role $role): void
    {
        $user->syncRoles([$role]);
        $user->unsetRelation('roles');
    }

    public function syncDirectPermissions(User $user, array $permissions): void
    {
        $user->syncPermissions($permissions);
        $user->unsetRelation('permissions');
    }

    public function syncDisciplines(User $user, array $disciplines): void
    {
        $user->syncDisciplines($disciplines);
    }

    public function withPermission(string $permission, ?int $exceptUserId = null): Collection
    {
        return $this->query()->active()
            ->where(fn (Builder $q) => $q
                ->whereHas('roles', fn (Builder $r) => $r->where('slug', PermissionCatalog::OWNER)
                    ->orWhereHas('permissions', fn (Builder $p) => $p->where('name', $permission)))
                ->orWhereHas('permissions', fn (Builder $p) => $p->where('name', $permission)))
            ->when($exceptUserId !== null, fn (Builder $q) => $q->whereKeyNot($exceptUserId))
            ->get();
    }

    public function activeCount(): int
    {
        return $this->query()->active()->count();
    }

    public function revokeTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}
