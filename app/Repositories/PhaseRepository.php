<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Repositories\Contracts\PhaseRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<ProjectPhase>
 */
class PhaseRepository extends BaseRepository implements PhaseRepositoryInterface
{
    protected function model(): string
    {
        return ProjectPhase::class;
    }

    public function forProject(Project $project): Collection
    {
        return $project->phases()->get();
    }

    public function nextOrder(Project $project): int
    {
        return (int) $project->phases()->max('sort_order') + 1;
    }

    public function idsFor(Project $project): array
    {
        return array_values(array_map('intval', $project->phases()->pluck('id')->all()));
    }

    public function reorder(Project $project, array $orderedIds): void
    {
        DB::transaction(function () use ($project, $orderedIds): void {
            foreach ($orderedIds as $index => $id) {
                ProjectPhase::query()
                    ->where('project_id', $project->id)
                    ->whereKey($id)
                    ->update(['sort_order' => $index + 1]);
            }
        });
    }
}
