<?php

namespace App\Http\Requests\Project;

use App\Enums\Status;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class PhaseRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:200'],
            'order' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', Rule::enum(Status::class)],
        ];
    }
}
