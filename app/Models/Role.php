<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * @property int $id
 * @property string|null $slug
 * @property string $name
 * @property string $guard_name
 * @property bool $is_system
 * @property int|null $users_count
 */
class Role extends SpatieRole
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    /**
     * Spatie resolves the user model from the *current* auth guard. Under auth:sanctum that
     * guard has no provider, so the relation is pinned to User here.
     *
     * @return MorphToMany<User, $this>
     */
    public function users(): MorphToMany
    {
        /** @var string $table */
        $table = config('permission.table_names.model_has_roles');
        /** @var string $morphKey */
        $morphKey = config('permission.column_names.model_morph_key');

        return $this->morphedByMany(User::class, 'model', $table, app(PermissionRegistrar::class)->pivotRole, $morphKey);
    }
}
