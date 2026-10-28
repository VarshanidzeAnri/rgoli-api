<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;

class SyncProjectMembersAction
{
    public function __construct(private ProjectRepositoryInterface $projects) {}

    /**
     * @param  list<int>  $userIds
     */
    public function handle(Project $project, array $userIds): Project
    {
        $this->projects->syncMembers($project, $userIds);

        return $this->projects->findWithDetails($project->id);
    }
}
