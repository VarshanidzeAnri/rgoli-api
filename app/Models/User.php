<?php

namespace App\Models;

use App\Support\PermissionCatalog;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $job_title
 * @property string|null $avatar_path
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, Permission> $permissions
 * @property-read Collection<int, UserDiscipline> $disciplineRows
 */
#[Fillable(['name', 'email', 'password', 'job_title', 'avatar_path', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /** @var list<string>|null */
    private ?array $disciplineCache = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<UserDiscipline, $this>
     */
    public function disciplineRows(): HasMany
    {
        return $this->hasMany(UserDiscipline::class);
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<Issue, $this>
     */
    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class);
    }

    /**
     * @return HasMany<SalaryRequest, $this>
     */
    public function salaryRequests(): HasMany
    {
        return $this->hasMany(SalaryRequest::class);
    }

    /**
     * @return list<string>
     */
    public function disciplineValues(): array
    {
        if ($this->relationLoaded('disciplineRows')) {
            return array_values($this->disciplineRows->map(fn (UserDiscipline $row): string => $row->discipline->value)->all());
        }

        if ($this->disciplineCache === null) {
            /** @var list<string> $values */
            $values = DB::table('discipline_user')->where('user_id', $this->id)->pluck('discipline')->all();
            $this->disciplineCache = $values;
        }

        return $this->disciplineCache;
    }

    /**
     * @param  list<string>  $disciplines
     */
    public function syncDisciplines(array $disciplines): void
    {
        DB::table('discipline_user')->where('user_id', $this->id)->delete();
        DB::table('discipline_user')->insert(array_map(
            fn (string $d): array => ['user_id' => $this->id, 'discipline' => $d],
            array_values(array_unique($disciplines)),
        ));
        $this->disciplineCache = null;
        $this->unsetRelation('disciplineRows');
    }

    public function isOwner(): bool
    {
        return $this->roles->contains(fn (Role $role): bool => $role->slug === PermissionCatalog::OWNER);
    }

    /**
     * @return list<string>
     */
    public function flatPermissions(): array
    {
        if ($this->isOwner()) {
            return PermissionCatalog::all();
        }

        $names = $this->getAllPermissions()->map(fn (Permission $p): string => $p->name)->all();
        sort($names);

        return $names;
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Initial letter used by avatars.
     */
    public function initial(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }
}
