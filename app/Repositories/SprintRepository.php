<?php

namespace App\Repositories;

use App\Enums\SprintState;
use App\Enums\Status;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use App\Repositories\Contracts\SprintRepositoryInterface;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<Sprint>
 */
class SprintRepository extends BaseRepository implements SprintRepositoryInterface
{
    protected function model(): string
    {
        return Sprint::class;
    }

    public function allWithVisibleIssues(User $user): Collection
    {
        return $this->query()
            ->with(['issues' => $this->visibleIssues($user)])
            ->orderByRaw('case state when ? then 0 when ? then 1 else 2 end', [SprintState::Active->value, SprintState::Planned->value])
            ->orderBy('start_date')
            ->get();
    }

    public function findWithVisibleIssues(int $id, User $user): Sprint
    {
        return $this->query()->with(['issues' => $this->visibleIssues($user)])->findOrFail($id);
    }

    public function hasActive(): bool
    {
        return $this->query()->where('state', SprintState::Active->value)->exists();
    }

    public function moveIssuesInto(Sprint $sprint, array $issueIds): void
    {
        DB::transaction(function () use ($sprint, $issueIds): void {
            $otherOpen = $this->query()
                ->where('state', '!=', SprintState::Completed->value)
                ->whereKeyNot($sprint->id)
                ->pluck('id');

            DB::table('issue_sprint')
                ->whereIn('sprint_id', $otherOpen)
                ->whereIn('issue_id', $issueIds)
                ->delete();

            $sprint->issues()->syncWithoutDetaching(
                collect($issueIds)->mapWithKeys(fn (int $id): array => [$id => ['added_at' => now()]])->all(),
            );
        });
    }

    public function detachIssue(Sprint $sprint, int $issueId): void
    {
        $sprint->issues()->detach($issueId);
    }

    public function unfinishedIssueIds(Sprint $sprint): array
    {
        $ids = $sprint->issues()
            ->where('status', '!=', Status::Done->value)
            ->pluck('issues.id')
            ->all();

        return array_values(array_map('intval', $ids));
    }

    /**
     * @return \Closure(BelongsToMany<Issue, Sprint>): void
     */
    private function visibleIssues(User $user): \Closure
    {
        return function (BelongsToMany $q) use ($user): void {
            $q->whereIn('issues.project_id', Project::query()->visibleTo($user)->select('id'))
                ->with(['project', 'assignees', 'sprints'])
                ->orderBy('position');
        };
    }
}
