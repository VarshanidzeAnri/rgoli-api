<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\User;

class IssuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('issues.view');
    }

    public function view(User $user, Issue $issue): bool
    {
        return $user->can('issues.view') && $issue->project->isVisibleTo($user);
    }

    public function update(User $user, Issue $issue): bool
    {
        return $user->can('issues.update') && $issue->project->isVisibleTo($user);
    }

    public function move(User $user, Issue $issue): bool
    {
        return $user->can('issues.move') && $issue->project->isVisibleTo($user);
    }

    public function delete(User $user, Issue $issue): bool
    {
        return $user->can('issues.delete') && $issue->project->isVisibleTo($user);
    }

    public function comment(User $user, Issue $issue): bool
    {
        return $user->can('issues.comment') && $issue->project->isVisibleTo($user);
    }
}
