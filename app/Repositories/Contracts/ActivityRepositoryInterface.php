<?php

namespace App\Repositories\Contracts;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ActivityRepositoryInterface
{
    /**
     * @param  array{user_id: int|null, project_id: int|null, subject_type: string, subject_id: int, event: string, meta: array<string, mixed>}  $entry
     */
    public function record(array $entry): ActivityLog;

    /**
     * @return LengthAwarePaginator<int, ActivityLog>
     */
    public function paginateVisible(User $user, int $perPage): LengthAwarePaginator;

    /**
     * @return LengthAwarePaginator<int, ActivityLog>
     */
    public function paginateForProject(Project $project, int $perPage): LengthAwarePaginator;

    /**
     * @return Collection<int, ActivityLog>
     */
    public function latestVisible(User $user, int $limit): Collection;
}
