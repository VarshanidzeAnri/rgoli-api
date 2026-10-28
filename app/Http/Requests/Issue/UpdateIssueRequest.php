<?php

namespace App\Http\Requests\Issue;

use App\Enums\Priority;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * Status changes go through PATCH /issues/{id}/move (issues.move), not here.
 */
class UpdateIssueRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'priority' => ['sometimes', Rule::enum(Priority::class)],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'points' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'assignee_ids' => ['sometimes', 'array'],
            'assignee_ids.*' => ['integer', Rule::exists('users', 'id')],
        ];
    }
}
