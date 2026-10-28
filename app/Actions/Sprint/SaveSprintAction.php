<?php

namespace App\Actions\Sprint;

use App\Enums\SprintState;
use App\Models\Sprint;
use App\Repositories\Contracts\SprintRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Creates a sprint (always "planned") or edits one that isn't completed.
 */
class SaveSprintAction
{
    public function __construct(private SprintRepositoryInterface $sprints) {}

    /**
     * @param  array{name?: string, goal?: string|null, start_date?: string, end_date?: string}  $data
     */
    public function handle(array $data, ?Sprint $sprint = null): Sprint
    {
        if ($sprint === null) {
            return $this->sprints->create([...$data, 'state' => SprintState::Planned]);
        }

        if ($sprint->state === SprintState::Completed) {
            throw ValidationException::withMessages(['sprint' => __('messages.sprint_completed')]);
        }

        return $this->sprints->update($sprint, $data);
    }
}
