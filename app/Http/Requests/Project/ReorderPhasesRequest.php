<?php

namespace App\Http\Requests\Project;

use App\Http\Requests\ApiRequest;

class ReorderPhasesRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phase_ids' => ['required', 'array', 'min:1'],
            'phase_ids.*' => ['integer', 'distinct'],
        ];
    }
}
