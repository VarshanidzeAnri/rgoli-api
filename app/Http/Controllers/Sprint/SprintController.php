<?php

namespace App\Http\Controllers\Sprint;

use App\Actions\Sprint\AddIssuesToSprintAction;
use App\Actions\Sprint\CompleteSprintAction;
use App\Actions\Sprint\DeleteSprintAction;
use App\Actions\Sprint\RemoveIssueFromSprintAction;
use App\Actions\Sprint\SaveSprintAction;
use App\Actions\Sprint\StartSprintAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sprint\CompleteSprintRequest;
use App\Http\Requests\Sprint\SprintIssuesRequest;
use App\Http\Requests\Sprint\SprintRequest;
use App\Http\Resources\SprintResource;
use App\Models\Issue;
use App\Models\Sprint;
use App\Repositories\Contracts\IssueRepositoryInterface;
use App\Repositories\Contracts\SprintRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Sprints
 */
class SprintController extends Controller
{
    public function __construct(private SprintRepositoryInterface $sprints) {}

    /**
     * All sprints with their visible issues and progress stats.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Sprint::class);

        return SprintResource::collection($this->sprints->allWithVisibleIssues($this->actor($request)));
    }

    /**
     * Create a planned sprint (sprints.manage).
     */
    public function store(SprintRequest $request, SaveSprintAction $action): JsonResponse
    {
        $this->authorize('create', Sprint::class);
        /** @var array{name: string, goal?: string|null, start_date: string, end_date: string} $data */
        $data = $request->validated();
        $sprint = $action->handle($data);

        return $this->respond($request, $sprint, 201);
    }

    /**
     * Edit a sprint that isn't completed.
     */
    public function update(SprintRequest $request, Sprint $sprint, SaveSprintAction $action): JsonResponse
    {
        $this->authorize('update', $sprint);
        /** @var array{name?: string, goal?: string|null, start_date?: string, end_date?: string} $data */
        $data = $request->validated();
        $action->handle($data, $sprint);

        return $this->respond($request, $sprint);
    }

    /**
     * Delete a sprint; its issues return to the backlog.
     */
    public function destroy(Sprint $sprint, DeleteSprintAction $action): JsonResponse
    {
        $this->authorize('delete', $sprint);
        $action->handle($sprint);

        return $this->message('messages.deleted');
    }

    /**
     * Start a planned sprint (only one can be active).
     */
    public function start(Request $request, Sprint $sprint, StartSprintAction $action): JsonResponse
    {
        $this->authorize('update', $sprint);
        $action->handle($sprint);

        return $this->respond($request, $sprint);
    }

    /**
     * Complete the active sprint; unfinished issues go to move_open_to or the backlog.
     */
    public function complete(CompleteSprintRequest $request, Sprint $sprint, CompleteSprintAction $action): JsonResponse
    {
        $this->authorize('update', $sprint);
        $target = $request->validated('move_open_to');
        $action->handle($sprint, is_numeric($target) ? (int) $target : null);

        return $this->respond($request, $sprint);
    }

    /**
     * Add issues (moves them out of any other open sprint).
     */
    public function addIssues(SprintIssuesRequest $request, Sprint $sprint, IssueRepositoryInterface $issues, AddIssuesToSprintAction $action): JsonResponse
    {
        $this->authorize('update', $sprint);
        $ids = array_values(array_map('intval', (array) $request->validated('issue_ids')));
        if (! $issues->allVisible($this->actor($request), $ids)) {
            throw new AuthorizationException;
        }
        $action->handle($sprint, $ids);

        return $this->respond($request, $sprint);
    }

    /**
     * Remove one issue back to the backlog.
     */
    public function removeIssue(Request $request, Sprint $sprint, Issue $issue, RemoveIssueFromSprintAction $action): JsonResponse
    {
        $this->authorize('update', $sprint);
        $action->handle($sprint, $issue);

        return $this->respond($request, $sprint);
    }

    private function respond(Request $request, Sprint $sprint, int $status = 200): JsonResponse
    {
        return (new SprintResource($this->sprints->findWithVisibleIssues($sprint->id, $this->actor($request))))
            ->additional(['meta' => ['message' => __('messages.saved')]])
            ->response()->setStatusCode($status);
    }
}
