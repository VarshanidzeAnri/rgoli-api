<?php

namespace App\Http\Controllers\Issue;

use App\Actions\Issue\CreateIssueAction;
use App\Actions\Issue\DeleteIssueAction;
use App\Actions\Issue\MoveIssueAction;
use App\Actions\Issue\UpdateIssueAction;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\Issue\IndexIssueRequest;
use App\Http\Requests\Issue\MoveIssueRequest;
use App\Http\Requests\Issue\StoreIssueRequest;
use App\Http\Requests\Issue\UpdateIssueRequest;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Repositories\Contracts\IssueRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Issues
 */
class IssueController extends Controller
{
    private const BOARD_COLUMN_LIMIT = 300;

    public function __construct(private IssueRepositoryInterface $issues) {}

    /**
     * List visible issues.
     *
     * @queryParam project_id integer
     * @queryParam status string Comma-separated.
     * @queryParam priority string
     * @queryParam assignee integer User id.
     * @queryParam mine boolean
     * @queryParam sprint integer
     * @queryParam backlog boolean Not in an open sprint.
     * @queryParam due_from string Y-m-d
     * @queryParam due_to string Y-m-d
     * @queryParam search string
     * @queryParam sort string due_date, priority, created_at, position, points.
     */
    public function index(IndexIssueRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Issue::class);

        return IssueResource::collection($this->issues->paginateVisible($this->actor($request), $request->toFilters()));
    }

    /**
     * Board columns keyed by status. Accepts the list filters.
     */
    public function board(IndexIssueRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Issue::class);

        $columns = [];
        foreach ($this->issues->board($this->actor($request), $request->toFilters(), self::BOARD_COLUMN_LIMIT) as $status => $issues) {
            $columns[$status] = IssueResource::collection($issues)->resolve($request);
        }

        return response()->json(['data' => $columns]);
    }

    /**
     * Show an issue.
     */
    public function show(Issue $issue): IssueResource
    {
        $this->authorize('view', $issue);

        return new IssueResource($this->issues->findWithDetails($issue->id));
    }

    /**
     * Create an issue in a visible project.
     */
    public function store(StoreIssueRequest $request, ProjectRepositoryInterface $projects, CreateIssueAction $action): JsonResponse
    {
        $project = $projects->findOrFail((int) $request->validated('project_id'));
        $this->authorize('createIssue', $project);

        return (new IssueResource($action->handle($this->actor($request), $project, $request->validated())))
            ->additional(['meta' => ['message' => __('messages.saved')]])
            ->response()->setStatusCode(201);
    }

    /**
     * Quick edit: title, description, priority, due date, points, assignees.
     */
    public function update(UpdateIssueRequest $request, Issue $issue, UpdateIssueAction $action): IssueResource
    {
        $this->authorize('update', $issue);

        return (new IssueResource($action->handle($this->actor($request), $issue, $request->validated())))
            ->additional(['meta' => ['message' => __('messages.saved')]]);
    }

    /**
     * Move on the board: new status, placed directly below after_id (null = top).
     */
    public function move(MoveIssueRequest $request, Issue $issue, MoveIssueAction $action): IssueResource
    {
        $this->authorize('move', $issue);
        $afterId = $request->validated('after_id');

        $moved = $action->handle(
            $this->actor($request),
            $issue,
            Status::from((string) $request->validated('status')),
            is_numeric($afterId) ? (int) $afterId : null,
        );

        return (new IssueResource($moved))->additional(['meta' => ['message' => __('messages.moved')]]);
    }

    /**
     * Delete an issue (soft).
     */
    public function destroy(Issue $issue, DeleteIssueAction $action): JsonResponse
    {
        $this->authorize('delete', $issue);
        $action->handle($issue);

        return $this->message('messages.deleted');
    }
}
