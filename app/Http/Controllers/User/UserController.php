<?php

namespace App\Http\Controllers\User;

use App\Actions\User\SaveUserAction;
use App\Actions\User\SendUserPasswordResetAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\IndexUserRequest;
use App\Http\Requests\User\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Admin · Users
 */
class UserController extends Controller
{
    public function __construct(private UserRepositoryInterface $users) {}

    /**
     * List users (users.view).
     */
    public function index(IndexUserRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        return UserResource::collection($this->users->paginate($request->toFilters()));
    }

    /**
     * Show a user.
     */
    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($this->users->loadAccess($user));
    }

    /**
     * Create a user (users.manage).
     */
    public function store(UserRequest $request, SaveUserAction $action): JsonResponse
    {
        $this->authorize('create', User::class);

        return (new UserResource($action->handle($this->actor($request), $request->validated())))
            ->additional(['meta' => ['message' => __('messages.saved')]])
            ->response()->setStatusCode(201);
    }

    /**
     * Update a user, including role, extra permissions, disciplines and active flag.
     */
    public function update(UserRequest $request, User $user, SaveUserAction $action): UserResource
    {
        $this->authorize('update', $user);

        return (new UserResource($action->handle($this->actor($request), $request->validated(), $user)))
            ->additional(['meta' => ['message' => __('messages.saved')]]);
    }

    /**
     * Email the user a password reset link.
     */
    public function sendPasswordReset(Request $request, User $user, SendUserPasswordResetAction $action): JsonResponse
    {
        $this->authorize('update', $user);
        $action->handle($this->actor($request), $user);

        return $this->message('passwords.sent');
    }
}
