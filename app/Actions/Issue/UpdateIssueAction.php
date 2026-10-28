<?php

namespace App\Actions\Issue;

use App\Events\IssueAssigned;
use App\Models\Issue;
use App\Models\User;
use App\Repositories\Contracts\IssueRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Quick edits (title, description, priority, due date, points, assignees). Status changes use MoveIssueAction.
 */
class UpdateIssueAction
{
    public function __construct(private IssueRepositoryInterface $issues) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, Issue $issue, array $data): Issue
    {
        $added = DB::transaction(function () use ($issue, $data): array {
            $this->issues->update($issue, Arr::except($data, ['assignee_ids']));

            if (! array_key_exists('assignee_ids', $data)) {
                return [];
            }

            /** @var list<int> $ids */
            $ids = $data['assignee_ids'];

            return $this->issues->syncAssignees($issue, $ids);
        });

        if ($added !== []) {
            IssueAssigned::dispatch($issue, $added, $actor);
        }

        return $this->issues->findWithDetails($issue->id);
    }
}
