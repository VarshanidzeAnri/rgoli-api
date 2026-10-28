<?php

namespace App\Actions\Sprint;

use App\Enums\SprintState;
use App\Models\Sprint;
use App\Repositories\Contracts\SprintRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Deleting a sprint returns its issues to the backlog. Active sprints must be completed first.
 */
class DeleteSprintAction
{
    public function __construct(private SprintRepositoryInterface $sprints) {}

    public function handle(Sprint $sprint): void
    {
        if ($sprint->state === SprintState::Active) {
            throw ValidationException::withMessages(['sprint' => __('messages.sprint_active_delete')]);
        }

        $this->sprints->delete($sprint);
    }
}
