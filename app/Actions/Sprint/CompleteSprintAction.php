<?php

namespace App\Actions\Sprint;

use App\Enums\SprintState;
use App\Models\Sprint;
use App\Repositories\Contracts\SprintRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Completes the active sprint. Unfinished issues move to a planned sprint, or back to the
 * backlog when none is given (they stay attached to the completed sprint as history).
 */
class CompleteSprintAction
{
    public function __construct(private SprintRepositoryInterface $sprints) {}

    public function handle(Sprint $sprint, ?int $moveOpenTo): Sprint
    {
        if ($sprint->state !== SprintState::Active) {
            throw ValidationException::withMessages(['sprint' => __('messages.sprint_not_active')]);
        }

        DB::transaction(function () use ($sprint, $moveOpenTo): void {
            $unfinished = $this->sprints->unfinishedIssueIds($sprint);
            $this->sprints->update($sprint, ['state' => SprintState::Completed, 'completed_at' => now()]);

            if ($moveOpenTo !== null && $unfinished !== []) {
                $this->sprints->moveIssuesInto($this->sprints->findOrFail($moveOpenTo), $unfinished);
            }
        });

        return $sprint;
    }
}
