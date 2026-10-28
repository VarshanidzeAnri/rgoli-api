<?php

namespace App\Actions\Sprint;

use App\Enums\SprintState;
use App\Models\Sprint;
use App\Repositories\Contracts\SprintRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * An issue sits in at most one open sprint, so it is moved out of any other open sprint.
 */
class AddIssuesToSprintAction
{
    public function __construct(private SprintRepositoryInterface $sprints) {}

    /**
     * @param  list<int>  $issueIds
     */
    public function handle(Sprint $sprint, array $issueIds): void
    {
        if ($sprint->state === SprintState::Completed) {
            throw ValidationException::withMessages(['sprint' => __('messages.sprint_completed')]);
        }

        $this->sprints->moveIssuesInto($sprint, $issueIds);
    }
}
