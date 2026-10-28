<?php

namespace App\Actions\Profile;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UpdateAvatarAction
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function handle(User $user, UploadedFile $file): User
    {
        $path = $file->store('avatars', 'public');
        if ($path === false) {
            throw new RuntimeException('Avatar could not be stored.');
        }

        $old = $user->avatar_path;
        $this->users->update($user, ['avatar_path' => $path]);

        if ($old !== null) {
            Storage::disk('public')->delete($old);
        }

        return $user;
    }
}
