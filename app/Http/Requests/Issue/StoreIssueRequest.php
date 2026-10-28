<?php

namespace App\Http\Requests\Issue;

use App\Enums\Priority;
use App\Enums\Status;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class StoreIssueRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'status' => ['sometimes', Rule::enum(Status::class)],
            'priority' => ['sometimes', Rule::enum(Priority::class)],
            'due_date' => ['nullable', 'date'],
            'points' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'assignee_ids' => ['sometimes', 'array'],
            'assignee_ids.*' => ['integer', Rule::exists('users', 'id')],
            'sprint_id' => ['nullable', 'integer', Rule::exists('sprints', 'id')],
        ];
    }
}
