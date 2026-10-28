<?php

namespace App\Http\Controllers\Profile;

use App\Actions\Profile\ChangePasswordAction;
use App\Actions\Profile\UpdateAvatarAction;
use App\Actions\Profile\UpdateProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\AvatarRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\MeResource;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * @group Profile
 */
class ProfileController extends Controller
{
    public function __construct(private UserRepositoryInterface $users) {}

    /**
     * Current user: { user, roles[], permissions[] (flattened), disciplines[] }.
     */
    public function show(Request $request): MeResource
    {
        return new MeResource($this->users->loadAccess($this->actor($request)));
    }

    /**
     * Update name and job title.
     */
    public function update(UpdateProfileRequest $request, UpdateProfileAction $action): MeResource
    {
        /** @var array{name?: string, job_title?: string|null} $data */
        $data = $request->validated();
        $user = $action->handle($this->actor($request), $data);

        return (new MeResource($this->users->loadAccess($user)))->additional(['meta' => ['message' => __('messages.saved')]]);
    }

    /**
     * Upload avatar (jpg/png/webp, max 2 MB).
     */
    public function avatar(AvatarRequest $request, UpdateAvatarAction $action): MeResource
    {
        $file = $request->file('avatar');
        abort_unless($file instanceof UploadedFile, 422);

        $user = $action->handle($this->actor($request), $file);

        return (new MeResource($this->users->loadAccess($user)))->additional(['meta' => ['message' => __('messages.saved')]]);
    }

    /**
     * Change password (signs out other devices).
     */
    public function password(UpdatePasswordRequest $request, ChangePasswordAction $action): JsonResponse
    {
        $action->handle($this->actor($request), (string) $request->validated('password'));

        return $this->message('messages.password_changed');
    }
}
