<?php

namespace App\Http\Requests\Project;

use App\DTOs\Filters\ProjectFilters;
use App\Enums\Discipline;
use App\Http\Requests\ApiRequest;
use App\Http\Requests\Concerns\ParsesListParams;
use Illuminate\Validation\Rule;

/**
 * GET /projects?status=progress,review&discipline=architecture&search=..&mine=1&sort=-created_at
 */
class IndexProjectRequest extends ApiRequest
{
    use ParsesListParams;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->listRules(),
            'status' => ['sometimes', 'nullable', 'string', 'max:100'],
            'discipline' => ['sometimes', 'nullable', Rule::enum(Discipline::class)],
            'mine' => ['sometimes', 'boolean'],
        ];
    }

    public function toFilters(): ProjectFilters
    {
        [$sort, $direction] = $this->sortParam('created_at', 'desc');

        return new ProjectFilters(
            statuses: $this->csv('status'),
            discipline: $this->stringOrNull('discipline'),
            search: $this->stringOrNull('search'),
            onlyMine: $this->flag('mine'),
            sort: $sort,
            direction: $direction,
            perPage: $this->perPageParam(25),
        );
    }
}
