<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\ProjectPhase;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepositoryInterface<ProjectPhase>
 */
interface PhaseRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<int, ProjectPhase>
     */
    public function forProject(Project $project): Collection;

    public function nextOrder(Project $project): int;

    /**
     * @return list<int>
     */
    public function idsFor(Project $project): array;

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(Project $project, array $orderedIds): void;
}
