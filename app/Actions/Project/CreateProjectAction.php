<?php

namespace App\Actions\Project;

use App\Events\ProjectCreated;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Creates a project; the creator always becomes a member.
 */
class CreateProjectAction
{
    public function __construct(private ProjectRepositoryInterface $projects) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, array $data): Project
    {
        $project = DB::transaction(function () use ($actor, $data): Project {
            $project = $this->projects->create([
                ...Arr::except($data, ['member_ids']),
                'progress' => $data['progress'] ?? 0,
                'created_by' => $actor->id,
            ]);

            /** @var list<int> $memberIds */
            $memberIds = $data['member_ids'] ?? [];
            $this->projects->syncMembers($project, [...$memberIds, $actor->id]);

            return $project;
        });

        ProjectCreated::dispatch($project, $actor);

        return $this->projects->findWithDetails($project->id);
    }
}
