<?php

namespace App\Http\Resources;

use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Permission;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'is_system' => $this->is_system,
            'is_owner' => $this->slug === PermissionCatalog::OWNER,
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->map(fn (Permission $p): string => $p->name)->sort()->values()),
            'users_count' => $this->whenCounted('users'),
        ];
    }
}
