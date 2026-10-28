<?php

namespace App\Repositories;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ActivityRepository implements ActivityRepositoryInterface
{
    public function record(array $entry): ActivityLog
    {
        return ActivityLog::query()->create([...$entry, 'created_at' => now()]);
    }

    public function paginateVisible(User $user, int $perPage): LengthAwarePaginator
    {
        return ActivityLog::query()->visibleTo($user)
            ->with(['user', 'project'])
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage);
    }

    public function paginateForProject(Project $project, int $perPage): LengthAwarePaginator
    {
        return ActivityLog::query()
            ->where('project_id', $project->id)
            ->with(['user', 'project'])
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage);
    }

    public function latestVisible(User $user, int $limit): Collection
    {
        return ActivityLog::query()->visibleTo($user)
            ->with(['user', 'project'])
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
