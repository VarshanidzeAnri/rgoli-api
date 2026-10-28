<?php

namespace App\Http\Resources;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * GET /me: { user, roles[], permissions[] (flattened), disciplines[] }.
 *
 * @mixin User
 */
class MeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => [
                ...(new UserSummaryResource($this->resource))->toArray($request),
                'email' => $this->email,
            ],
            'roles' => $this->roles->map(fn (Role $r): array => ['id' => $r->id, 'slug' => $r->slug, 'name' => $r->name])->values(),
            'permissions' => $this->flatPermissions(),
            'disciplines' => $this->disciplineValues(),
        ];
    }
}
