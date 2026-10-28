<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        return $user->can('projects.view') && $project->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->can('projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->can('projects.update') && $project->isVisibleTo($user);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->can('projects.delete') && $project->isVisibleTo($user);
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $user->can('projects.manage_members') && $project->isVisibleTo($user);
    }

    public function managePhases(User $user, Project $project): bool
    {
        return $user->can('phases.manage') && $project->isVisibleTo($user);
    }

    public function updateLocation(User $user, Project $project): bool
    {
        return $user->can('locations.update') && $project->isVisibleTo($user);
    }

    public function viewFinance(User $user, Project $project): bool
    {
        return $user->can('finance.view') && $project->isVisibleTo($user);
    }

    public function managePayments(User $user, Project $project): bool
    {
        return $user->can('client_payments.manage') && $project->isVisibleTo($user);
    }

    public function createIssue(User $user, Project $project): bool
    {
        return $user->can('issues.create') && $project->isVisibleTo($user);
    }

    public function uploadFile(User $user, Project $project): bool
    {
        return $user->can('files.upload') && $project->isVisibleTo($user);
    }

    public function requestSalary(User $user, Project $project): bool
    {
        return $user->can('salary.request') && $project->isVisibleTo($user);
    }
}
