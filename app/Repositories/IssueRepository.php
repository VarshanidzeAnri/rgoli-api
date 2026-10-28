<?php

namespace App\Repositories;

use App\DTOs\Filters\IssueFilters;
use App\Enums\Priority;
use App\Enums\Status;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\IssueRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<Issue>
 */
class IssueRepository extends BaseRepository implements IssueRepositoryInterface
{
    /** @var list<string> */
    public const RELATIONS = ['project', 'assignees', 'sprints'];

    private const SORTABLE = ['due_date', 'priority', 'created_at', 'position', 'points'];

    protected function model(): string
    {
        return Issue::class;
    }

    public function paginateVisible(User $user, IssueFilters $filters): LengthAwarePaginator
    {
        $sort = in_array($filters->sort, self::SORTABLE, true) ? $filters->sort : 'position';

        return $this->filtered($user, $filters)
            ->orderBy($sort, $filters->direction === 'desc' ? 'desc' : 'asc')
            ->orderBy('id')
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    public function board(User $user, IssueFilters $filters, int $perColumn): array
    {
        $columns = [];
        foreach (Status::cases() as $status) {
            $columns[$status->value] = $this->filtered($user, $filters)
                ->where('status', $status->value)
                ->orderBy('position')
                ->orderBy('id')
                ->limit($perColumn)
                ->get();
        }

        return $columns;
    }

    public function findWithDetails(int $id): Issue
    {
        return $this->query()->with(self::RELATIONS)->withCount('comments')->findOrFail($id);
    }

    public function allVisible(User $user, array $ids): bool
    {
        $ids = array_values(array_unique($ids));

        return $this->query()->visibleTo($user)->whereKey($ids)->count() === count($ids);
    }

    public function nextNumber(Project $project): int
    {
        // Lock the project row so concurrent creates in the same project serialize.
        Project::query()->whereKey($project->id)->lockForUpdate()->first();

        return (int) Issue::withTrashed()->where('project_id', $project->id)->max('number') + 1;
    }

    public function nextPosition(Status $status): int
    {
        $max = $this->query()->where('status', $status->value)->max('position');

        return $max === null ? 0 : (int) $max + 1;
    }

    public function columnIdsForUpdate(Status $status, int $exceptId): array
    {
        $ids = $this->query()
            ->where('status', $status->value)
            ->whereKeyNot($exceptId)
            ->orderBy('position')
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('id')
            ->all();

        return array_values(array_map('intval', $ids));
    }

    public function savePositions(array $positions): void
    {
        foreach ($positions as $id => $position) {
            Issue::query()->whereKey($id)->where('position', '!=', $position)->update(['position' => $position]);
        }
    }

    public function syncAssignees(Issue $issue, array $userIds): array
    {
        $changes = $issue->assignees()->sync(array_values(array_unique($userIds)));

        return array_values(array_map('intval', $changes['attached']));
    }

    public function dueBetween(User $user, string $from, string $to, bool $onlyMine = false): Collection
    {
        $query = $this->query()->visibleTo($user)
            ->with(self::RELATIONS)
            ->whereBetween('due_date', [$from, $to])
            ->orderBy('due_date')
            ->orderBy('position');

        if ($onlyMine) {
            $query->whereHas('assignees', fn (Builder $a) => $a->whereKey($user->id));
        }

        return $query->get();
    }

    public function search(User $user, string $term, int $limit): Collection
    {
        $like = $this->like($term);

        return $this->query()->visibleTo($user)
            ->with('project')
            ->where('title', 'like', $like)
            ->limit($limit)
            ->get();
    }

    public function statusCounts(User $user, ?string $from = null, ?string $to = null): array
    {
        $query = $this->query()->visibleTo($user);
        if ($from !== null) {
            $query->whereDate('issues.created_at', '>=', $from);
        }
        if ($to !== null) {
            $query->whereDate('issues.created_at', '<=', $to);
        }

        $counts = $query->toBase()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        $result = [];
        foreach (Status::values() as $status) {
            $result[$status] = (int) ($counts[$status] ?? 0);
        }

        return $result;
    }

    public function openCriticalCount(User $user): int
    {
        return $this->query()->visibleTo($user)
            ->where('status', '!=', Status::Done->value)
            ->where('priority', Priority::Critical->value)
            ->count();
    }

    public function workload(User $user): Collection
    {
        $visible = $this->query()->visibleTo($user)->where('status', '!=', Status::Done->value)->select('id');

        /** @var Collection<int, object{user_id: int, open_count: int, open_points: int}> $rows */
        $rows = DB::table('issue_user')
            ->join('issues', 'issues.id', '=', 'issue_user.issue_id')
            ->whereIn('issue_user.issue_id', $visible)
            ->groupBy('issue_user.user_id')
            ->selectRaw('issue_user.user_id as user_id, count(*) as open_count, coalesce(sum(issues.points), 0) as open_points')
            ->get()
            ->map(fn (object $r): object => (object) [
                'user_id' => (int) $r->user_id,
                'open_count' => (int) $r->open_count,
                'open_points' => (int) $r->open_points,
            ]);

        return $rows;
    }

    /**
     * @return Builder<Issue>
     */
    private function filtered(User $user, IssueFilters $f): Builder
    {
        $query = $this->query()->visibleTo($user)->with(self::RELATIONS)->withCount('comments');

        if ($f->projectId !== null) {
            $query->where('project_id', $f->projectId);
        }
        if ($f->statuses !== []) {
            $query->whereIn('status', $f->statuses);
        }
        if ($f->priority !== null) {
            $query->where('priority', $f->priority);
        }
        if ($f->assigneeId !== null) {
            $query->whereHas('assignees', fn (Builder $a) => $a->whereKey($f->assigneeId));
        }
        if ($f->onlyMine) {
            $query->whereHas('assignees', fn (Builder $a) => $a->whereKey($user->id));
        }
        if ($f->sprintId !== null) {
            $query->whereHas('sprints', fn (Builder $s) => $s->whereKey($f->sprintId));
        }
        if ($f->backlog) {
            $query->inBacklog();
        }
        if ($f->dueFrom !== null) {
            $query->whereDate('due_date', '>=', $f->dueFrom);
        }
        if ($f->dueTo !== null) {
            $query->whereDate('due_date', '<=', $f->dueTo);
        }
        if ($f->search !== null && $f->search !== '') {
            $query->where('title', 'like', $this->like($f->search));
        }

        return $query;
    }
}
