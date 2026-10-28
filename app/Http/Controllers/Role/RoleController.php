<?php

namespace App\Http\Controllers\Role;

use App\Actions\Role\DeleteRoleAction;
use App\Actions\Role\SaveRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Support\PermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Admin · Roles
 */
class RoleController extends Controller
{
    public function __construct(private RoleRepositoryInterface $roles) {}

    /**
     * Roles with permissions and user counts.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Role::class);

        return RoleResource::collection($this->roles->allWithPermissions());
    }

    /**
     * Permission catalog grouped by module.
     */
    public function permissions(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return response()->json(['data' => PermissionCatalog::groups()]);
    }

    /**
     * Create a role (roles.manage).
     */
    public function store(RoleRequest $request, SaveRoleAction $action): JsonResponse
    {
        $this->authorize('create', Role::class);
        /** @var array{name: string, permissions?: list<string>} $data */
        $data = $request->validated();

        return (new RoleResource($action->handle($this->actor($request), $data)))
            ->additional(['meta' => ['message' => __('messages.saved')]])
            ->response()->setStatusCode(201);
    }

    /**
     * Rename a role and/or set its permissions.
     */
    public function update(RoleRequest $request, Role $role, SaveRoleAction $action): RoleResource
    {
        $this->authorize('update', $role);
        /** @var array{name?: string, permissions?: list<string>} $data */
        $data = $request->validated();

        return (new RoleResource($action->handle($this->actor($request), $data, $role)))
            ->additional(['meta' => ['message' => __('messages.saved')]]);
    }

    /**
     * Delete a custom role that has no users.
     */
    public function destroy(Request $request, Role $role, DeleteRoleAction $action): JsonResponse
    {
        $this->authorize('delete', $role);
        $action->handle($this->actor($request), $role);

        return $this->message('messages.deleted');
    }
}
