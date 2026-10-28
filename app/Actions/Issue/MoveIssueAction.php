<?php

namespace App\Actions\Issue;

use App\Enums\Status;
use App\Events\IssueMoved;
use App\Models\Issue;
use App\Models\User;
use App\Repositories\Contracts\IssueRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Drops an issue into a board column directly below $afterId (null = top) and renumbers the column.
 * Relative placement keeps ordering correct even when the board is filtered.
 */
class MoveIssueAction
{
    public function __construct(private IssueRepositoryInterface $issues) {}

    public function handle(User $actor, Issue $issue, Status $status, ?int $afterId): Issue
    {
        $from = $issue->status;

        DB::transaction(function () use ($issue, $status, $afterId): void {
            $ids = $this->issues->columnIdsForUpdate($status, $issue->id);

            $index = 0;
            if ($afterId !== null) {
                $found = array_search($afterId, $ids, true);
                $index = $found === false ? count($ids) : $found + 1;
            }
            array_splice($ids, $index, 0, [$issue->id]);

            $this->issues->update($issue, ['status' => $status, 'position' => $index]);
            $this->issues->savePositions(array_flip($ids));
        });

        if ($from !== $status) {
            IssueMoved::dispatch($issue, $from, $status, $actor);
        }

        return $this->issues->findWithDetails($issue->id);
    }
}
