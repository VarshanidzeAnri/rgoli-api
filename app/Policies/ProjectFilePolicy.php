<?php

namespace App\Policies;

use App\Models\ProjectFile;
use App\Models\User;

class ProjectFilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('files.view');
    }

    public function view(User $user, ProjectFile $file): bool
    {
        return $user->can('files.view') && $file->project->isVisibleTo($user);
    }

    public function delete(User $user, ProjectFile $file): bool
    {
        if (! $file->project->isVisibleTo($user)) {
            return false;
        }

        return $user->can('files.delete_any')
            || ($user->can('files.delete_own') && $file->uploaded_by === $user->id);
    }
}
