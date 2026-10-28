<?php

namespace App\Repositories\Contracts;

use App\DTOs\Filters\ProjectFilters;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepositoryInterface<Project>
 */
interface ProjectRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginateVisible(User $user, ProjectFilters $filters): LengthAwarePaginator;

    /**
     * Number of visible projects per status, honouring every filter except status.
     *
     * @return array<string, int>
     */
    public function statusCounts(User $user, ProjectFilters $filters): array;

    public function isVisibleTo(Project $project, User $user): bool;

    /**
     * Project with phases, members, current phase and paid/task totals.
     */
    public function findWithDetails(int $id): Project;

    /**
     * @param  list<int>  $userIds
     */
    public function syncMembers(Project $project, array $userIds): void;

    /**
     * Soft-deletes the project together with its issues, files, salary requests and payments.
     */
    public function softDeleteWithChildren(Project $project): void;

    /**
     * Visible projects with coordinates, for the map.
     *
     * @return Collection<int, Project>
     */
    public function forMap(User $user): Collection;

    /**
     * Visible projects overlapping a date range, with phases, for the timeline.
     *
     * @return Collection<int, Project>
     */
    public function forTimeline(User $user, string $from, string $to): Collection;

    /**
     * Visible projects ending inside a date range (calendar milestones).
     *
     * @return Collection<int, Project>
     */
    public function endingBetween(User $user, string $from, string $to): Collection;

    /**
     * Visible projects with totals (dashboard, finance, reports).
     *
     * @return Collection<int, Project>
     */
    public function allVisibleWithTotals(User $user): Collection;

    /**
     * Projects the user is a member of.
     *
     * @return Collection<int, Project>
     */
    public function memberOf(User $user, int $limit): Collection;

    /**
     * @return Collection<int, Project>
     */
    public function search(User $user, string $term, int $limit): Collection;

    public function keyExists(string $key): bool;
}
