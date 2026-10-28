<?php

namespace App\Repositories\Contracts;

use App\Models\Sprint;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepositoryInterface<Sprint>
 */
interface SprintRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Sprints with only the issues visible to the user. Completed sprints last.
     *
     * @return Collection<int, Sprint>
     */
    public function allWithVisibleIssues(User $user): Collection;

    public function findWithVisibleIssues(int $id, User $user): Sprint;

    public function hasActive(): bool;

    /**
     * Removes the issues from every open sprint other than $sprint, then attaches them to it.
     *
     * @param  list<int>  $issueIds
     */
    public function moveIssuesInto(Sprint $sprint, array $issueIds): void;

    public function detachIssue(Sprint $sprint, int $issueId): void;

    /**
     * @return list<int>
     */
    public function unfinishedIssueIds(Sprint $sprint): array;
}
