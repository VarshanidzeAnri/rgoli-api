<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;

/**
 * Soft-deletes the project with its tasks, files, salary requests and client payments.
 */
class DeleteProjectAction
{
    public function __construct(private ProjectRepositoryInterface $projects) {}

    public function handle(Project $project): void
    {
        $this->projects->softDeleteWithChildren($project);
    }
}
