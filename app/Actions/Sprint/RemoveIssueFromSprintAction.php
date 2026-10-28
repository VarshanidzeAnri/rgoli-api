<?php

namespace App\Actions\Sprint;

use App\Enums\SprintState;
use App\Models\Issue;
use App\Models\Sprint;
use App\Repositories\Contracts\SprintRepositoryInterface;
use Illuminate\Validation\ValidationException;

class RemoveIssueFromSprintAction
{
    public function __construct(private SprintRepositoryInterface $sprints) {}

    public function handle(Sprint $sprint, Issue $issue): void
    {
        if ($sprint->state === SprintState::Completed) {
            throw ValidationException::withMessages(['sprint' => __('messages.sprint_completed')]);
        }

        $this->sprints->detachIssue($sprint, $issue->id);
    }
}
