<?php

namespace App\Http\Resources;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Permission;

/**
 * Full user record for the admin screens.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...(new UserSummaryResource($this->resource))->toArray($request),
            'email' => $this->email,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->map(fn (Role $r): array => [
                'id' => $r->id, 'slug' => $r->slug, 'name' => $r->name,
            ])->values()),
            'direct_permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->map(fn (Permission $p): string => $p->name)->values()),
            'permissions' => $this->when($this->relationLoaded('roles') && $this->relationLoaded('permissions'), fn () => $this->flatPermissions()),
            'disciplines' => $this->disciplineValues(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
