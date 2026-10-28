<?php

namespace App\Repositories\Contracts;

use App\DTOs\Filters\IssueFilters;
use App\Enums\Status;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepositoryInterface<Issue>
 */
interface IssueRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Issue>
     */
    public function paginateVisible(User $user, IssueFilters $filters): LengthAwarePaginator;

    /**
     * Visible issues grouped by status, each column ordered by position.
     *
     * @return array<string, Collection<int, Issue>>
     */
    public function board(User $user, IssueFilters $filters, int $perColumn): array;

    /**
     * Issue with project, assignees, sprints and comment count.
     */
    public function findWithDetails(int $id): Issue;

    /**
     * True when every id belongs to an issue on a project visible to the user.
     *
     * @param  list<int>  $ids
     */
    public function allVisible(User $user, array $ids): bool;

    /**
     * Next per-project issue number. Must run inside a transaction (it locks).
     */
    public function nextNumber(Project $project): int;

    public function nextPosition(Status $status): int;

    /**
     * Ordered ids of a board column, locked for update. Must run inside a transaction.
     *
     * @return list<int>
     */
    public function columnIdsForUpdate(Status $status, int $exceptId): array;

    /**
     * @param  array<int, int>  $positions  issue id => position
     */
    public function savePositions(array $positions): void;

    /**
     * @param  list<int>  $userIds
     * @return list<int> ids that were newly attached
     */
    public function syncAssignees(Issue $issue, array $userIds): array;

    /**
     * Visible issues with a due date inside the range (calendar, "my week").
     *
     * @return Collection<int, Issue>
     */
    public function dueBetween(User $user, string $from, string $to, bool $onlyMine = false): Collection;

    /**
     * @return Collection<int, Issue>
     */
    public function search(User $user, string $term, int $limit): Collection;

    /**
     * Counts of visible issues per status.
     *
     * @return array<string, int>
     */
    public function statusCounts(User $user, ?string $from = null, ?string $to = null): array;

    public function openCriticalCount(User $user): int;

    /**
     * Open issue count and points per assignee (visible issues only).
     *
     * @return Collection<int, object{user_id: int, open_count: int, open_points: int}>
     */
    public function workload(User $user): Collection;
}
