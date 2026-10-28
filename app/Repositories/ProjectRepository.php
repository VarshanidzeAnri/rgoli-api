<?php

namespace App\Repositories;

use App\DTOs\Filters\ProjectFilters;
use App\Enums\Status;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<Project>
 */
class ProjectRepository extends BaseRepository implements ProjectRepositoryInterface
{
    private const SORTABLE = ['name', 'end_date', 'progress', 'created_at'];

    /** @var list<string> */
    private const LIST_RELATIONS = ['currentPhase', 'members'];

    protected function model(): string
    {
        return Project::class;
    }

    public function paginateVisible(User $user, ProjectFilters $filters): LengthAwarePaginator
    {
        $sort = in_array($filters->sort, self::SORTABLE, true) ? $filters->sort : 'created_at';

        $query = $this->filtered($user, $filters)
            ->withTotals()
            ->with(self::LIST_RELATIONS)
            ->orderBy($sort, $filters->direction === 'asc' ? 'asc' : 'desc')
            ->orderBy('id');

        if ($filters->statuses !== []) {
            $query->whereIn('status', $filters->statuses);
        }

        return $query->paginate($filters->perPage)->withQueryString();
    }

    public function statusCounts(User $user, ProjectFilters $filters): array
    {
        $counts = $this->filtered($user, $filters)
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $result = [];
        foreach (Status::values() as $status) {
            $result[$status] = (int) ($counts[$status] ?? 0);
        }

        return $result;
    }

    public function isVisibleTo(Project $project, User $user): bool
    {
        return $project->isVisibleTo($user);
    }

    public function findWithDetails(int $id): Project
    {
        return $this->query()
            ->withTotals()
            ->with([...self::LIST_RELATIONS, 'phases'])
            ->findOrFail($id);
    }

    public function syncMembers(Project $project, array $userIds): void
    {
        $project->members()->sync(array_values(array_unique($userIds)));
    }

    public function softDeleteWithChildren(Project $project): void
    {
        DB::transaction(function () use ($project): void {
            $now = now();
            $project->issues()->update(['deleted_at' => $now]);
            $project->files()->update(['deleted_at' => $now]);
            $project->salaryRequests()->update(['deleted_at' => $now]);
            $project->clientPayments()->update(['deleted_at' => $now]);
            $project->delete();
        });
    }

    public function forMap(User $user): Collection
    {
        return $this->query()->visibleTo($user)
            ->with('currentPhase')
            ->orderBy('id')
            ->get();
    }

    public function forTimeline(User $user, string $from, string $to): Collection
    {
        return $this->query()->visibleTo($user)
            ->with(['phases', 'currentPhase'])
            ->where(fn (Builder $q) => $q->whereNull('start_date')->orWhereDate('start_date', '<=', $to))
            ->where(fn (Builder $q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $from))
            ->orderBy('start_date')
            ->get();
    }

    public function endingBetween(User $user, string $from, string $to): Collection
    {
        return $this->query()->visibleTo($user)
            ->whereBetween('end_date', [$from, $to])
            ->orderBy('end_date')
            ->get();
    }

    public function allVisibleWithTotals(User $user): Collection
    {
        return $this->query()->visibleTo($user)
            ->withTotals()
            ->with('currentPhase')
            ->orderByDesc('created_at')
            ->get();
    }

    public function memberOf(User $user, int $limit): Collection
    {
        return $this->query()
            ->whereHas('members', fn (Builder $m) => $m->whereKey($user->id))
            ->where('status', '!=', Status::Done->value)
            ->orderBy('end_date')
            ->limit($limit)
            ->get();
    }

    public function search(User $user, string $term, int $limit): Collection
    {
        $like = $this->like($term);

        return $this->query()->visibleTo($user)
            ->where(fn (Builder $q) => $q->where('name', 'like', $like)
                ->orWhere('client_name', 'like', $like)
                ->orWhere('address', 'like', $like)
                ->orWhere('key', 'like', $like))
            ->limit($limit)
            ->get();
    }

    public function keyExists(string $key): bool
    {
        return Project::withTrashed()->where('key', $key)->exists();
    }

    /**
     * Visibility + every filter except status.
     *
     * @return Builder<Project>
     */
    private function filtered(User $user, ProjectFilters $filters): Builder
    {
        $query = $this->query()->visibleTo($user);

        if ($filters->discipline !== null) {
            $query->where('discipline', $filters->discipline);
        }

        if ($filters->search !== null && $filters->search !== '') {
            $like = $this->like($filters->search);
            $query->where(fn (Builder $q) => $q->where('name', 'like', $like)
                ->orWhere('client_name', 'like', $like)
                ->orWhere('address', 'like', $like)
                ->orWhere('key', 'like', $like));
        }

        if ($filters->onlyMine) {
            $query->whereHas('members', fn (Builder $m) => $m->whereKey($user->id));
        }

        return $query;
    }
}
