<?php

namespace App\Actions\User;

use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Creates or updates a user with role, extra direct permissions and disciplines.
 * Deactivating a user revokes their tokens.
 */
class SaveUserAction
{
    public function __construct(
        private UserRepositoryInterface $users,
        private RoleRepositoryInterface $roles,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, array $data, ?User $user = null): User
    {
        if ($user !== null && $user->id === $actor->id && array_key_exists('is_active', $data) && ! $data['is_active']) {
            throw ValidationException::withMessages(['is_active' => __('messages.cannot_deactivate_self')]);
        }

        $saved = DB::transaction(function () use ($data, $user): User {
            $attributes = Arr::only($data, ['name', 'email', 'password', 'job_title', 'is_active']);
            if (isset($attributes['email']) && is_string($attributes['email'])) {
                $attributes['email'] = mb_strtolower(trim($attributes['email']));
            }

            $user = $user === null ? $this->users->create($attributes) : $this->users->update($user, $attributes);

            if (isset($data['role_id'])) {
                $this->users->assignRole($user, $this->roles->findOrFail((int) $data['role_id']));
            }

            if (array_key_exists('direct_permissions', $data)) {
                /** @var list<string> $permissions */
                $permissions = $data['direct_permissions'];
                $this->users->syncDirectPermissions($user, $permissions);
            }

            if (array_key_exists('disciplines', $data)) {
                /** @var list<string> $disciplines */
                $disciplines = $data['disciplines'];
                $this->users->syncDisciplines($user, $disciplines);
            }

            if (! $user->is_active) {
                $this->users->revokeTokens($user);
            }

            return $user;
        });

        Log::channel('auth')->info('user.saved', [
            'user_id' => $saved->id,
            'by' => $actor->id,
            'fields' => array_keys(Arr::except($data, ['password'])),
        ]);

        return $this->users->loadAccess($saved);
    }
}
