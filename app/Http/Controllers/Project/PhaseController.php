<?php

namespace App\Http\Controllers\Project;

use App\Actions\Phase\CreatePhaseAction;
use App\Actions\Phase\DeletePhaseAction;
use App\Actions\Phase\ReorderPhasesAction;
use App\Actions\Phase\UpdatePhaseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\PhaseRequest;
use App\Http\Requests\Project\ReorderPhasesRequest;
use App\Http\Resources\PhaseResource;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Repositories\Contracts\PhaseRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Phases
 */
class PhaseController extends Controller
{
    /**
     * Phases of a project, in order.
     */
    public function index(Project $project, PhaseRepositoryInterface $phases): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        return PhaseResource::collection($phases->forProject($project));
    }

    /**
     * Add a phase (phases.manage).
     */
    public function store(PhaseRequest $request, Project $project, CreatePhaseAction $action): JsonResponse
    {
        $this->authorize('managePhases', $project);
        /** @var array{name: string, order?: int, start_date?: string|null, end_date?: string|null, status?: string} $data */
        $data = $request->validated();

        return (new PhaseResource($action->handle($project, $data)))
            ->additional(['meta' => ['message' => __('messages.saved')]])
            ->response()->setStatusCode(201);
    }

    /**
     * Update a phase.
     */
    public function update(PhaseRequest $request, ProjectPhase $phase, UpdatePhaseAction $action): PhaseResource
    {
        $this->authorize('managePhases', $phase->project);

        return (new PhaseResource($action->handle($phase, $request->validated())))
            ->additional(['meta' => ['message' => __('messages.saved')]]);
    }

    /**
     * Delete a phase.
     */
    public function destroy(ProjectPhase $phase, DeletePhaseAction $action): JsonResponse
    {
        $this->authorize('managePhases', $phase->project);
        $action->handle($phase);

        return $this->message('messages.deleted');
    }

    /**
     * Reorder all phases of a project.
     */
    public function reorder(ReorderPhasesRequest $request, Project $project, ReorderPhasesAction $action): AnonymousResourceCollection
    {
        $this->authorize('managePhases', $project);
        /** @var list<int> $ids */
        $ids = array_map('intval', (array) $request->validated('phase_ids'));

        return PhaseResource::collection($action->handle($project, $ids))
            ->additional(['meta' => ['message' => __('messages.saved')]]);
    }
}
