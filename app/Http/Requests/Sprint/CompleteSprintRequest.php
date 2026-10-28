<?php

namespace App\Http\Requests\Sprint;

use App\Enums\SprintState;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class CompleteSprintRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'move_open_to' => ['nullable', 'integer', Rule::exists('sprints', 'id')->where('state', SprintState::Planned->value)],
        ];
    }
}
