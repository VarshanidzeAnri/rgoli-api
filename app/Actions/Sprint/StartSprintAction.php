<?php

namespace App\Actions\Sprint;

use App\Enums\SprintState;
use App\Models\Sprint;
use App\Repositories\Contracts\SprintRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Starts a planned sprint. Only one sprint can be active at a time.
 */
class StartSprintAction
{
    public function __construct(private SprintRepositoryInterface $sprints) {}

    public function handle(Sprint $sprint): Sprint
    {
        if ($sprint->state !== SprintState::Planned) {
            throw ValidationException::withMessages(['sprint' => __('messages.sprint_not_planned')]);
        }

        if ($this->sprints->hasActive()) {
            throw ValidationException::withMessages(['sprint' => __('messages.sprint_already_active')]);
        }

        return $this->sprints->update($sprint, ['state' => SprintState::Active, 'started_at' => now()]);
    }
}
