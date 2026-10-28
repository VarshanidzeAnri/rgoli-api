<?php

namespace App\Http\Requests\Issue;

use App\DTOs\Filters\IssueFilters;
use App\Enums\Priority;
use App\Http\Requests\ApiRequest;
use App\Http\Requests\Concerns\ParsesListParams;
use Illuminate\Validation\Rule;

/**
 * GET /issues and /issues/board:
 * ?project_id=&status=a,b&priority=&assignee=&mine=1&sprint=&backlog=1&due_from=&due_to=&search=&sort=
 */
class IndexIssueRequest extends ApiRequest
{
    use ParsesListParams;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->listRules(),
            'project_id' => ['sometimes', 'nullable', 'integer'],
            'status' => ['sometimes', 'nullable', 'string', 'max:100'],
            'priority' => ['sometimes', 'nullable', Rule::enum(Priority::class)],
            'assignee' => ['sometimes', 'nullable', 'integer'],
            'mine' => ['sometimes', 'boolean'],
            'sprint' => ['sometimes', 'nullable', 'integer'],
            'backlog' => ['sometimes', 'boolean'],
            'due_from' => ['sometimes', 'nullable', 'date'],
            'due_to' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function toFilters(): IssueFilters
    {
        [$sort, $direction] = $this->sortParam('position');

        return new IssueFilters(
            projectId: $this->intOrNull('project_id'),
            statuses: $this->csv('status'),
            priority: $this->stringOrNull('priority'),
            assigneeId: $this->intOrNull('assignee'),
            onlyMine: $this->flag('mine'),
            sprintId: $this->intOrNull('sprint'),
            backlog: $this->flag('backlog'),
            dueFrom: $this->stringOrNull('due_from'),
            dueTo: $this->stringOrNull('due_to'),
            search: $this->stringOrNull('search'),
            sort: $sort,
            direction: $direction,
            perPage: $this->perPageParam(50),
        );
    }
}
