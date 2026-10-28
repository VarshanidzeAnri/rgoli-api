<?php

namespace App\Http\Controllers\Project;

use App\Actions\Project\CreateProjectAction;
use App\Actions\Project\DeleteProjectAction;
use App\Actions\Project\SyncProjectMembersAction;
use App\Actions\Project\UpdateProjectAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\IndexProjectRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\SyncMembersRequest;
use App\Http\Requests\Project\UpdateLocationRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Projects
 */
class ProjectController extends Controller
{
    public function __construct(private ProjectRepositoryInterface $projects) {}

    /**
     * List visible projects (status chip counts in meta.status_counts).
     *
     * @queryParam status string Comma-separated. Example: progress,review
     * @queryParam discipline string Example: architecture
     * @queryParam search string Name, client, address or key.
     * @queryParam mine boolean Only projects I am a member of.
     * @queryParam sort string name, end_date, progress, created_at; prefix "-" for desc. Example: -created_at
     */
    public function index(IndexProjectRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);
        $user = $this->actor($request);
        $filters = $request->toFilters();

        return ProjectResource::collection($this->projects->paginateVisible($user, $filters))
            ->additional(['meta' => ['status_counts' => $this->projects->statusCounts($user, $filters)]]);
    }

    /**
     * Create a project.
     */
    public function store(StoreProjectRequest $request, CreateProjectAction $action): JsonResponse
    {
        $this->authorize('create', Project::class);
        $project = $action->handle($this->actor($request), $request->validated());

        return (new ProjectResource($project))
            ->additional(['meta' => ['message' => __('messages.saved')]])
            ->response()->setStatusCode(201);
    }

    /**
     * Project details with phases, members and totals.
     */
    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        return new ProjectResource($this->projects->findWithDetails($project->id));
    }

    /**
     * Update a project.
     */
    public function update(UpdateProjectRequest $request, Project $project, UpdateProjectAction $action): ProjectResource
    {
        $this->authorize('update', $project);

        return (new ProjectResource($action->handle($project, $request->validated())))
            ->additional(['meta' => ['message' => __('messages.saved')]]);
    }

    /**
     * Soft-delete a project with its tasks, files, payments and requests.
     */
    public function destroy(Project $project, DeleteProjectAction $action): JsonResponse
    {
        $this->authorize('delete', $project);
        $action->handle($project);

        return $this->message('messages.deleted');
    }

    /**
     * Set map coordinates (locations.update).
     */
    public function location(UpdateLocationRequest $request, Project $project, UpdateProjectAction $action): ProjectResource
    {
        $this->authorize('updateLocation', $project);

        return (new ProjectResource($action->handle($project, $request->validated())))
            ->additional(['meta' => ['message' => __('messages.saved')]]);
    }

    /**
     * Replace the member list (projects.manage_members).
     */
    public function members(SyncMembersRequest $request, Project $project, SyncProjectMembersAction $action): ProjectResource
    {
        $this->authorize('manageMembers', $project);
        /** @var list<int> $ids */
        $ids = $request->validated('member_ids');

        return (new ProjectResource($action->handle($project, $ids)))
            ->additional(['meta' => ['message' => __('messages.saved')]]);
    }

    /**
     * Activity feed of one project.
     */
    public function activity(Request $request, Project $project, ActivityRepositoryInterface $activity): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        return ActivityResource::collection($activity->paginateForProject($project, $this->perPage($request, 30)));
    }

    /**
     * Visible projects for the map.
     */
    public function map(Request $request): AnonymousResourceCollection
    {
        return ProjectResource::collection($this->projects->forMap($this->actor($request)));
    }

    /**
     * Visible projects with phases for the Gantt timeline.
     *
     * @queryParam from string required Y-m-d.
     * @queryParam to string required Y-m-d.
     */
    public function timeline(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);
        $range = $request->validate(['from' => ['required', 'date'], 'to' => ['required', 'date', 'after_or_equal:from']]);

        return ProjectResource::collection($this->projects->forTimeline($this->actor($request), (string) $range['from'], (string) $range['to']));
    }
}
