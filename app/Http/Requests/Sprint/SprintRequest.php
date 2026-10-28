<?php

namespace App\Http\Requests\Sprint;

use App\Http\Requests\ApiRequest;

class SprintRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:120'],
            'goal' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'start_date' => [$required, 'date'],
            'end_date' => [$required, 'date', 'after_or_equal:start_date'],
        ];
    }
}
