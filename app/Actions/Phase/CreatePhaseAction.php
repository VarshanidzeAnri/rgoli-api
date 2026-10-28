<?php

namespace App\Actions\Phase;

use App\Enums\Status;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Repositories\Contracts\PhaseRepositoryInterface;

class CreatePhaseAction
{
    public function __construct(private PhaseRepositoryInterface $phases) {}

    /**
     * @param  array{name: string, order?: int, start_date?: string|null, end_date?: string|null, status?: string}  $data
     */
    public function handle(Project $project, array $data): ProjectPhase
    {
        return $this->phases->create([
            'project_id' => $project->id,
            'name' => $data['name'],
            'sort_order' => $data['order'] ?? $this->phases->nextOrder($project),
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? Status::Todo->value,
        ]);
    }
}
