<?php

namespace App\Actions\Issue;

use App\Enums\Priority;
use App\Enums\Status;
use App\Events\IssueAssigned;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\IssueRepositoryInterface;
use App\Repositories\Contracts\SprintRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Creates an issue with the next per-project number, at the bottom of its column.
 */
class CreateIssueAction
{
    public function __construct(
        private IssueRepositoryInterface $issues,
        private SprintRepositoryInterface $sprints,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, Project $project, array $data): Issue
    {
        /** @var list<int> $assigneeIds */
        $assigneeIds = $data['assignee_ids'] ?? [];
        $status = Status::from((string) ($data['status'] ?? Status::Todo->value));

        $issue = DB::transaction(function () use ($actor, $project, $data, $assigneeIds, $status): Issue {
            $issue = $this->issues->create([
                'project_id' => $project->id,
                'number' => $this->issues->nextNumber($project),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => $status,
                'priority' => $data['priority'] ?? Priority::Medium->value,
                'due_date' => $data['due_date'] ?? null,
                'points' => $data['points'] ?? 1,
                'position' => $this->issues->nextPosition($status),
                'created_by' => $actor->id,
            ]);

            $this->issues->syncAssignees($issue, $assigneeIds);

            if (isset($data['sprint_id'])) {
                $this->sprints->moveIssuesInto($this->sprints->findOrFail((int) $data['sprint_id']), [$issue->id]);
            }

            return $issue;
        });

        if ($assigneeIds !== []) {
            IssueAssigned::dispatch($issue, $assigneeIds, $actor);
        }

        return $this->issues->findWithDetails($issue->id);
    }
}
