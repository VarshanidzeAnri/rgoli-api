<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;

/**
 * Updates project fields. Also used for location changes (lat/lng/address only).
 */
class UpdateProjectAction
{
    public function __construct(private ProjectRepositoryInterface $projects) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Project $project, array $data): Project
    {
        $this->projects->update($project, $data);

        return $this->projects->findWithDetails($project->id);
    }
}
