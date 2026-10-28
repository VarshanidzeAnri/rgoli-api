<?php

namespace App\Actions\Phase;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Repositories\Contracts\PhaseRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ReorderPhasesAction
{
    public function __construct(private PhaseRepositoryInterface $phases) {}

    /**
     * @param  list<int>  $orderedIds  must contain exactly the project's phase ids
     * @return Collection<int, ProjectPhase>
     */
    public function handle(Project $project, array $orderedIds): Collection
    {
        $existing = $this->phases->idsFor($project);
        sort($existing);
        $given = $orderedIds;
        sort($given);

        if ($existing !== $given) {
            throw ValidationException::withMessages(['phase_ids' => __('messages.phases_mismatch')]);
        }

        $this->phases->reorder($project, $orderedIds);

        return $this->phases->forProject($project);
    }
}
